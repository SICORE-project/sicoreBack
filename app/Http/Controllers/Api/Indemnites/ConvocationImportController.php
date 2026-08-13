<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\ImportConvocationsRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\TypeConvocation;
use App\Models\Parametrage\Enseignant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Option A du workflow "Transmission des convocations à la DAGE" : la
 * DECPC remet un fichier (CSV ou Word) listant les convocations à créer,
 * la DAGE l'importe ici. Chaque ligne du tableau devient UNE convocation
 * (avec, si les colonnes sont renseignées, un centre d'examen et un
 * bénéficiaire rattachés) — cf. GUIDE-IMPORT-CONVOCATIONS.md pour le
 * format exact.
 *
 * Une ligne dont une information est absente ou non reconnue (agent
 * introuvable, type inconnu...) n'est PAS rejetée : elle est quand même
 * créée avec le statut "brouillon", pour que la DAGE la retrouve dans
 * la liste et la complète via le formulaire (option B). Seule une ligne
 * totalement invalide (objet impossible à déterminer) est ignorée et
 * remontée en erreur.
 */
class ConvocationImportController extends Controller
{
    use ApiResponseTrait;

    /**
     * En-têtes acceptées (normalisées : minuscules, sans accent, espaces
     * et underscores retirés) pour chaque colonne logique.
     */
    private const ALIAS = [
        'matricule' => ['matricule'],
        'agent' => ['agent', 'nomagent', 'enseignant', 'nomprenom', 'nom'],
        'type' => ['type', 'typeconvocation'],
        'session' => ['session'],
        'centre' => ['centre', 'centreexamen'],
        'role' => ['role', 'fonction'],
        'provenance' => ['provenance'],
        'date_debut' => ['datedebut', 'debut', 'du'],
        'date_fin' => ['datefin', 'fin', 'au'],
        'date_emission' => ['dateemission', 'demission'],
        'lieu_examen' => ['lieuexamen', 'lieu'],
        'objet' => ['objet'],
    ];

    public function store(ImportConvocationsRequest $request)
    {
        $fichier = $request->file('fichier');
        $chemin = $fichier->getRealPath();
        $extension = Str::lower((string) $fichier->getClientOriginalExtension());
        $utilisateurId = (int) $request->validated('utilisateur_id');

        try {
            [$ligneEntete, $lignesDonnees] = $extension === 'docx'
                ? $this->lireDocx($chemin)
                : $this->lireCsv($chemin);
        } catch (\Throwable $e) {
            return $this->error("Impossible de lire le fichier transmis : {$e->getMessage()}", 422);
        }

        if (! $ligneEntete) {
            return $this->error(
                $extension === 'docx'
                    ? "Aucun tableau trouvé dans ce document Word."
                    : "Le fichier est vide ou son entête est illisible.",
                422
            );
        }

        $colonnes = $this->indexerColonnes($ligneEntete);

        $crees = [];
        $avertissements = [];
        $erreurs = [];
        $numeroLigne = 1; // la ligne 1 est l'entete

        foreach ($lignesDonnees as $ligne) {
            $numeroLigne++;

            // Ignore les lignes entierement vides (fin de fichier, lignes
            // blanches laissees par Excel/Word...).
            if (count(array_filter($ligne, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            try {
                $resultat = DB::transaction(function () use ($ligne, $colonnes, $utilisateurId) {
                    return $this->importerLigne($ligne, $colonnes, $utilisateurId);
                });

                $crees[] = array_merge(['ligne' => $numeroLigne], $resultat['resume']);

                foreach ($resultat['avertissements'] as $avertissement) {
                    $avertissements[] = "Ligne {$numeroLigne} : {$avertissement}";
                }
            } catch (\Throwable $e) {
                $erreurs[] = "Ligne {$numeroLigne} : {$e->getMessage()}";
            }
        }

        return $this->success(
            count($crees) > 0
                ? count($crees).' convocation(s) importée(s).'
                : "Aucune convocation n'a pu être importée.",
            [
                'importees' => count($crees),
                'convocations' => $crees,
                'avertissements' => $avertissements,
                'erreurs' => $erreurs,
            ],
            201
        );
    }

    /**
     * Lit un fichier CSV : renvoie [ligne d'entete, lignes de donnees].
     */
    private function lireCsv(string $chemin): array
    {
        $poignee = fopen($chemin, 'r');

        if ($poignee === false) {
            throw new \RuntimeException('lecture impossible.');
        }

        $separateur = $this->detecterSeparateur($chemin);

        // Retire le BOM UTF-8 eventuel (Excel l'ajoute systematiquement),
        // sinon la 1ere colonne de l'entete ne matche jamais son alias.
        $premiersOctets = fread($poignee, 3);
        if ($premiersOctets !== "\xEF\xBB\xBF") {
            rewind($poignee);
        }

        $ligneEntete = fgetcsv($poignee, 0, $separateur);

        if (! $ligneEntete) {
            fclose($poignee);

            return [null, []];
        }

        $lignes = [];

        while (($ligne = fgetcsv($poignee, 0, $separateur)) !== false) {
            $lignes[] = $ligne;
        }

        fclose($poignee);

        return [$ligneEntete, $lignes];
    }

    /**
     * Lit le premier tableau d'un document Word (.docx) : renvoie
     * [ligne d'entete, lignes de donnees], sur le meme principe que
     * lireCsv() (1re ligne du tableau = en-tetes, memes alias de
     * colonnes que le format CSV — cf. self::ALIAS).
     */
    private function lireDocx(string $chemin): array
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($chemin);

        $table = null;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $table = $element;
                    break 2;
                }
            }
        }

        if (! $table) {
            return [null, []];
        }

        $lignesBrutes = [];

        foreach ($table->getRows() as $ligne) {
            $cellules = [];

            foreach ($ligne->getCells() as $cellule) {
                $cellules[] = $this->extraireTexteCellule($cellule);
            }

            $lignesBrutes[] = $cellules;
        }

        if (empty($lignesBrutes)) {
            return [null, []];
        }

        $ligneEntete = array_shift($lignesBrutes);

        return [$ligneEntete, $lignesBrutes];
    }

    /**
     * Concatene le texte d'une cellule de tableau Word (les elements
     * "Text" simples et les "TextRun" imbriques suffisent pour un
     * tableau de convocation classique — pas de gestion des liens,
     * notes de bas de page ou objets plus complexes).
     */
    private function extraireTexteCellule(\PhpOffice\PhpWord\Element\Cell $cellule): string
    {
        $morceaux = [];

        foreach ($cellule->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $texte = $element->getText();

                if (is_string($texte)) {
                    $morceaux[] = $texte;
                }
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $sousElement) {
                    if (method_exists($sousElement, 'getText')) {
                        $texte = $sousElement->getText();

                        if (is_string($texte)) {
                            $morceaux[] = $texte;
                        }
                    }
                }
            }
        }

        return trim(implode(' ', array_filter($morceaux, fn ($t) => $t !== '')));
    }

    /**
     * Cree la convocation (+ centre + beneficiaire eventuels) pour une
     * ligne du fichier. Renvoie un resume de ce qui a ete cree ainsi que
     * les avertissements (info manquante ou non reconnue -> statut
     * "brouillon").
     */
    private function importerLigne(array $ligne, array $colonnes, int $utilisateurId): array
    {
        $valeur = fn (string $cle) => isset($colonnes[$cle], $ligne[$colonnes[$cle]])
            ? trim((string) $ligne[$colonnes[$cle]])
            : null;

        $avertissements = [];
        $incomplete = false;

        // --- Type de convocation -------------------------------------
        $typeLibelle = $valeur('type');
        $type = null;

        if ($typeLibelle) {
            $type = TypeConvocation::whereRaw('LOWER(libelle) = ?', [Str::lower($typeLibelle)])
                ->orWhereRaw('LOWER(code) = ?', [Str::lower($typeLibelle)])
                ->first();

            if (! $type) {
                $avertissements[] = "type de convocation « {$typeLibelle} » non reconnu.";
                $incomplete = true;
            }
        } else {
            $incomplete = true;
        }

        // --- Dates -----------------------------------------------------
        $dateEmission = $this->parserDate($valeur('date_emission')) ?? Carbon::today();
        $dateDebut = $this->parserDate($valeur('date_debut'));
        $dateFin = $this->parserDate($valeur('date_fin'));

        if ($valeur('date_debut') && ! $dateDebut) {
            $avertissements[] = "date de début « {$valeur('date_debut')} » illisible.";
        }
        if ($valeur('date_fin') && ! $dateFin) {
            $avertissements[] = "date de fin « {$valeur('date_fin')} » illisible.";
        }
        if (! $dateDebut || ! $dateFin) {
            $incomplete = true;
        }

        // --- Session / lieu / objet ------------------------------------
        $session = $valeur('session');
        $lieuExamen = $valeur('lieu_examen');
        $objet = $valeur('objet') ?: trim(($type->libelle ?? $typeLibelle ?? 'Convocation').' '.($session ?? ''));

        if (! $session) {
            $incomplete = true;
        }
        if (! $lieuExamen) {
            $incomplete = true;
        }

        // --- Agent -------------------------------------------------------
        $matricule = $valeur('matricule');
        $nomAgent = $valeur('agent');
        $enseignant = null;

        if ($matricule) {
            $enseignant = Enseignant::where('matricule', $matricule)->first();

            if (! $enseignant) {
                $avertissements[] = "aucun agent trouvé pour le matricule « {$matricule} ».";
            }
        } elseif ($nomAgent) {
            $enseignant = $this->trouverEnseignantParNom($nomAgent);

            if (! $enseignant) {
                $avertissements[] = "aucun agent trouvé pour « {$nomAgent} » (ou plusieurs correspondances).";
            }
        } else {
            $avertissements[] = 'aucun agent renseigné.';
        }

        if (! $enseignant) {
            $incomplete = true;
        }

        // --- Centre --------------------------------------------------
        $nomCentre = $valeur('centre');
        if (! $nomCentre) {
            $incomplete = true;
        }

        // --- Rôle ------------------------------------------------------
        $role = $valeur('role');
        if (! $role) {
            $incomplete = true;
        }

        // --- Provenance --------------------------------------------------
        $provenance = $valeur('provenance');

        // --- Persistance -------------------------------------------------
        $convocation = ConvocationModel::create([
            'type_convocation_id' => $type->id ?? null,
            'date_emission' => $dateEmission,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'objet' => $objet !== '' ? $objet : 'Convocation importée',
            'session' => $session,
            'lieu_examen' => $lieuExamen,
            'statut' => $incomplete ? 'brouillon' : 'emise',
            'utilisateur_id' => $utilisateurId,
        ]);

        $centreId = null;

        if ($nomCentre) {
            $centre = $convocation->centres()->create([
                'centre' => $nomCentre,
            ]);

            $centreId = $centre->id;
        }

        if ($enseignant) {
            $convocation->enseignants()->attach($enseignant->id, [
                'fonction' => $role,
                'centre_id' => $centreId,
                'provenance' => $provenance,
            ]);
        }

        return [
            'resume' => [
                'convocation_id' => $convocation->id,
                'agent' => $enseignant?->nom_complet ?? $nomAgent ?? $matricule,
                'centre' => $nomCentre,
                'statut' => $convocation->statut,
            ],
            'avertissements' => $avertissements,
        ];
    }

    /**
     * Recherche un enseignant par nom complet, dans les deux ordres
     * possibles ("Prénom Nom" et "Nom Prénom") puisque le fichier de la
     * DECPC ne garantit pas un ordre fixe. Renvoie null si aucune
     * correspondance unique n'est trouvée (ambiguïté = à compléter
     * manuellement plutôt que de deviner).
     */
    private function trouverEnseignantParNom(string $nomComplet): ?Enseignant
    {
        $mots = preg_split('/\s+/', trim($nomComplet), -1, PREG_SPLIT_NO_EMPTY);

        if (count($mots) < 2) {
            $resultats = Enseignant::whereRaw('LOWER(nom) = ?', [Str::lower($nomComplet)])->get();

            return $resultats->count() === 1 ? $resultats->first() : null;
        }

        $premier = Str::lower(array_shift($mots));
        $reste = Str::lower(implode(' ', $mots));

        $resultats = Enseignant::where(function ($q) use ($premier, $reste) {
            $q->where(function ($q2) use ($premier, $reste) {
                $q2->whereRaw('LOWER(prenom) = ?', [$premier])
                    ->whereRaw('LOWER(nom) = ?', [$reste]);
            })->orWhere(function ($q2) use ($premier, $reste) {
                $q2->whereRaw('LOWER(nom) = ?', [$premier])
                    ->whereRaw('LOWER(prenom) = ?', [$reste]);
            });
        })->get();

        return $resultats->count() === 1 ? $resultats->first() : null;
    }

    private function parserDate(?string $valeur): ?Carbon
    {
        if (! $valeur) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $valeur)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($valeur);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Associe chaque alias reconnu (cf. self::ALIAS) à l'index de colonne
     * correspondant dans l'entête du fichier.
     */
    private function indexerColonnes(array $ligneEntete): array
    {
        $normalisees = array_map([$this, 'normaliserEntete'], $ligneEntete);

        $colonnes = [];

        foreach (self::ALIAS as $cle => $alias) {
            foreach ($alias as $candidat) {
                $index = array_search($candidat, $normalisees, true);

                if ($index !== false) {
                    $colonnes[$cle] = $index;
                    break;
                }
            }
        }

        return $colonnes;
    }

    private function normaliserEntete(string $entete): string
    {
        $entete = Str::lower(trim($entete));
        $entete = Str::ascii($entete); // retire les accents
        return preg_replace('/[^a-z0-9]/', '', $entete);
    }

    /**
     * Devine le séparateur CSV (',' ou ';') en comptant lequel des deux
     * produit le plus de colonnes sur la première ligne — Excel FR
     * exporte en ';' par défaut, les autres outils souvent en ','.
     */
    private function detecterSeparateur(string $chemin): string
    {
        $premiereLigne = '';
        $poignee = fopen($chemin, 'r');

        if ($poignee !== false) {
            $premiereLigne = (string) fgets($poignee);
            fclose($poignee);
        }

        return substr_count($premiereLigne, ';') > substr_count($premiereLigne, ',') ? ';' : ',';
    }
}
