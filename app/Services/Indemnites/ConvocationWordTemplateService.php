<?php

namespace App\Services\Indemnites;

use App\Models\Parametrage\Enseignant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Table as WordTable;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class ConvocationWordTemplateService
{
    // Nom versionné : changer de version force la régénération du modèle
    // (cheminModele() ne le génère que s'il n'existe pas encore).
    private const CHEMIN_MODELE = 'convocations/modele-convocation-v2.docx';

    private const CHAMPS_INFOS = [
        'Objet' => 'objet',
        'Session' => 'session',
        "Date d'émission (jj/mm/aaaa)" => 'date_emission',
        'Statut de la convocation (brouillon, emise, envoyee, cloturee)' => 'statut',
        'Date début (jj/mm/aaaa)' => 'date_debut',
        'Date fin (jj/mm/aaaa)' => 'date_fin',
        'Heure début (hh:mm)' => 'heure_debut',
        "Lieu d'affectation" => 'lieu_affectation',
    ];

    // Colonnes écrites dans le modèle : uniquement le matricule pour les
    // personnes — téléphone, catégorie de personnel et provenance (lieu de
    // service) sont retrouvés en base à partir du matricule.
    private const CHAMPS_CENTRE = [
        "Centre d'examen" => 'centre',
        'Jury' => 'jury',
        'Métier / spécialité' => 'metier',
        'Matricule du chef de centre' => 'chef_centre_matricule',
        'Matricule du président du jury' => 'president_jury_matricule',
    ];

    // Colonnes des anciens modèles (saisie par nom), toujours lues à
    // l'import pour ne pas casser un document déjà rempli. Une valeur saisie
    // dans l'une d'elles prime sur celle trouvée en base.
    private const ALIAS_ANCIENS_MODELES_CENTRE = [
        'Chef de centre (nom complet)' => 'chef_centre',
        'Téléphone du chef de centre' => 'chef_centre_telephone',
        'Provenance du chef de centre' => 'chef_centre_provenance',
        'Catégorie de personnel du chef de centre' => 'chef_centre_categorie_personnel',
        'Président du jury (nom complet)' => 'president_jury',
        'Téléphone du président du jury' => 'president_jury_telephone',
        'Provenance du président du jury' => 'president_jury_provenance',
        'Catégorie de personnel du président du jury' => 'president_jury_categorie_personnel',
    ];

    private const CHAMPS_MEMBRE = [
        'Matricule' => 'matricule',
        "Centre de rattachement (nom exact d'un centre ci-dessus)" => 'centre_nom',
        'Fonction (Correction ou Surveillant)' => 'fonction',
    ];

    private const ALIAS_ANCIENS_MODELES_MEMBRE = [
        'Prénom' => 'prenom',
        'Nom' => 'nom',
        'Fonction' => 'fonction',
        'Catégorie de personnel' => 'categorie_personnel',
        'Provenance' => 'provenance',
        'Téléphone' => 'telephone',
    ];

    // Titres des tableaux tels qu'affichés dans le document (et dans les messages d'import).
    public const TABLEAU_INFOS = 'Informations générales';

    public const TABLEAU_CENTRES = "Centres d'examen";

    public const TABLEAU_MEMBRES = 'Membres du jury';

    private const MARQUEUR_INFOS = 'objet';

    private const MARQUEUR_CENTRE = 'centredexamen';

    // Nouveau modèle : "Matricule" ; anciens modèles : "Prénom".
    private const MARQUEURS_MEMBRE = ['matricule', 'prenom'];

   
    public function cheminModele(): string
    {
        if (! Storage::disk('public')->exists(self::CHEMIN_MODELE)) {
            $this->genererModele();
        }

        return self::CHEMIN_MODELE;
    }

    private function genererModele(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(self::TABLEAU_INFOS, ['bold' => true]);
        $this->ajouterTableau($section, array_keys(self::CHAMPS_INFOS), 10);

        $section->addTextBreak();
        $section->addText(self::TABLEAU_CENTRES, ['bold' => true]);
        $this->ajouterTableau($section, array_keys(self::CHAMPS_CENTRE), 2);

        $section->addTextBreak();
        $section->addText(self::TABLEAU_MEMBRES, ['bold' => true]);
        $this->ajouterTableau($section, array_keys(self::CHAMPS_MEMBRE), 2);

        $chemin = Storage::disk('public')->path(self::CHEMIN_MODELE);

        if (! is_dir(dirname($chemin))) {
            mkdir(dirname($chemin), 0755, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($chemin);
    }

    
    private function ajouterTableau($section, array $entetes, int $lignesVides): WordTable
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);

        $ligneEntete = $table->addRow();
        foreach ($entetes as $libelle) {
            $ligneEntete->addCell(2200)->addText($libelle, ['bold' => true]);
        }

        for ($i = 0; $i < $lignesVides; $i++) {
            $ligne = $table->addRow();
            foreach ($entetes as $libelle) {
                $ligne->addCell(2200)->addText('');
            }
        }

        return $table;
    }

    
    public function lire(string $cheminFichier): array
    {
        $phpWord = IOFactory::load($cheminFichier);
        $tables = $this->extraireTableaux($phpWord);

        $avertissements = [];

        $tableInfos = $this->trouverTableau($tables, [self::MARQUEUR_INFOS]);
        $tableCentre = $this->trouverTableau($tables, [self::MARQUEUR_CENTRE]);
        // Le tableau Centres contient lui aussi "Matricule" (chef de centre,
        // président du jury) : on l'exclut de la recherche du tableau Membres.
        $tableMembre = $this->trouverTableau(
            array_filter($tables, fn ($t) => $t !== $tableCentre && $t !== $tableInfos),
            self::MARQUEURS_MEMBRE
        );

        $convocation = [];
        $centres = [];
        $beneficiaires = [];

        // Chaque avertissement indique où se trouve le problème dans le
        // document : tableau, numéro de ligne (1 = première ligne remplie
        // sous l'en-tête) et, si possible, colonne.
        if (! $tableInfos) {
            $avertissements[] = self::TABLEAU_INFOS.' : tableau introuvable (la première ligne doit contenir la colonne « Objet »).';
        } else {
            $colonnes = $this->mapperColonnes($tableInfos, self::CHAMPS_INFOS);
            $lignes = $this->lireLignesDonnees($tableInfos, $colonnes, self::CHAMPS_INFOS);

            if (empty($lignes)) {
                $avertissements[] = self::TABLEAU_INFOS.' : aucune ligne remplie sous l\'en-tête.';
            } else {
                $avertissementsLigne = [];
                $convocation = $this->resoudreInfos($lignes[array_key_first($lignes)], $avertissementsLigne);
                $this->localiser($avertissements, $avertissementsLigne, self::TABLEAU_INFOS);
            }
        }

        if (! $tableCentre) {
            $avertissements[] = self::TABLEAU_CENTRES.' : tableau introuvable (la première ligne doit contenir la colonne « Centre d\'examen »).';
        } else {
            $champs = self::CHAMPS_CENTRE + self::ALIAS_ANCIENS_MODELES_CENTRE;
            $colonnes = $this->mapperColonnes($tableCentre, $champs);

            foreach ($this->lireLignesDonnees($tableCentre, $colonnes, $champs) as $numero => $ligne) {
                $avertissementsLigne = [];
                $centre = $this->resoudreCentre($ligne, $avertissementsLigne);
                $this->localiser($avertissements, $avertissementsLigne, self::TABLEAU_CENTRES, $numero);

                if ($centre) {
                    $centres[] = $centre + ['ligne' => $numero];
                } else {
                    $avertissements[] = self::TABLEAU_CENTRES.", ligne {$numero} : colonne « Centre d'examen » vide — ligne ignorée.";
                }
            }
        }

        if (! $tableMembre) {
            $avertissements[] = self::TABLEAU_MEMBRES.' : tableau introuvable (la première ligne doit contenir la colonne « Matricule »).';
        } else {
            $champs = self::CHAMPS_MEMBRE + self::ALIAS_ANCIENS_MODELES_MEMBRE;
            $colonnes = $this->mapperColonnes($tableMembre, $champs);

            foreach ($this->lireLignesDonnees($tableMembre, $colonnes, $champs) as $numero => $ligne) {
                $avertissementsLigne = [];
                $membre = $this->resoudreMembre($ligne, $avertissementsLigne);
                $this->localiser($avertissements, $avertissementsLigne, self::TABLEAU_MEMBRES, $numero);

                if ($membre) {
                    $beneficiaires[] = $membre + ['ligne' => $numero];
                }
            }
        }

        return [
            'convocation' => $convocation,
            'centres' => $centres,
            'beneficiaires' => $beneficiaires,
            'avertissements' => $avertissements,
        ];
    }

    /**
     * Ajoute à $avertissements ceux d'une ligne, préfixés par leur emplacement
     * dans le document (« Membres du jury, ligne 3 : … »).
     */
    private function localiser(array &$avertissements, array $avertissementsLigne, string $tableau, ?int $numero = null): void
    {
        $emplacement = $numero === null ? $tableau : "{$tableau}, ligne {$numero}";

        foreach ($avertissementsLigne as $avertissement) {
            $avertissements[] = "{$emplacement} : {$avertissement}";
        }
    }

    /**
     * Intitulé de colonne du document Word correspondant à un champ interne,
     * pour des messages de validation compréhensibles (« Téléphone du chef de
     * centre » plutôt que « centres.0.chef_centre_telephone »).
     */
    public function libelleColonne(string $tableau, string $champ): string
    {
        $champs = match ($tableau) {
            self::TABLEAU_INFOS => self::CHAMPS_INFOS,
            self::TABLEAU_CENTRES => self::CHAMPS_CENTRE + self::ALIAS_ANCIENS_MODELES_CENTRE,
            default => self::CHAMPS_MEMBRE + self::ALIAS_ANCIENS_MODELES_MEMBRE,
        };

        $libelle = array_search($champ, $champs, true);

        return $libelle === false ? $champ : preg_replace('/\s*\(.*\)$/', '', $libelle);
    }

    /**
     * @return WordTable[]
     */
    private function extraireTableaux(PhpWord $phpWord): array
    {
        $tables = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof WordTable) {
                    $tables[] = $element;
                }
            }
        }

        return $tables;
    }

  
    /**
     * @param  string[]  $marqueurs  le premier marqueur trouvé dans un en-tête l'emporte
     */
    private function trouverTableau(array $tables, array $marqueurs): ?WordTable
    {
        foreach ($marqueurs as $marqueur) {
            if ($table = $this->trouverTableauParMarqueur($tables, $marqueur)) {
                return $table;
            }
        }

        return null;
    }

    private function trouverTableauParMarqueur(array $tables, string $marqueur): ?WordTable
    {
        foreach ($tables as $table) {
            $lignes = $table->getRows();

            if (empty($lignes)) {
                continue;
            }

            foreach ($lignes[0]->getCells() as $cellule) {
                if (str_contains($this->normaliser($this->texteCellule($cellule)), $marqueur)) {
                    return $table;
                }
            }
        }

        return null;
    }

   
    private function mapperColonnes(WordTable $table, array $champs): array
    {
        $alias = [];
        foreach ($champs as $libelle => $champInterne) {
            $alias[$this->normaliser($libelle)] = $champInterne;
        }
        uksort($alias, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $colonnes = [];
        $entete = $table->getRows()[0]->getCells();

        foreach ($entete as $index => $cellule) {
            $normalise = $this->normaliser($this->texteCellule($cellule));

            foreach ($alias as $cleAlias => $champInterne) {
                if (str_contains($normalise, $cleAlias)) {
                    $colonnes[$index] = $champInterne;

                    break;
                }
            }
        }

        return $colonnes;
    }

    /**
     * Lit toutes les lignes de données (après l'en-tête), en ignorant les
     * lignes entièrement vides.
     *
     * @param  array<int, string>  $colonnes  index de colonne => champ interne
     * @return array<int, array<string, string>> une entrée par ligne : champ interne => valeur brute
     */
    private function lireLignesDonnees(WordTable $table, array $colonnes, array $champs): array
    {
        $donnees = [];
        // Un modèle plus ancien peut ne pas avoir toutes les colonnes : celles qui manquent valent ''.
        $parDefaut = array_fill_keys(array_values($champs), '');

        // Clé = numéro de la ligne dans le tableau (1 = première ligne sous
        // l'en-tête), conservé pour situer les erreurs dans le document.
        foreach (array_slice($table->getRows(), 1) as $index => $ligne) {
            $valeurs = array_merge($parDefaut, $this->lireLigne($ligne, $colonnes));

            if (count(array_filter($valeurs, fn ($v) => $v !== '')) > 0) {
                $donnees[$index + 1] = $valeurs;
            }
        }

        return $donnees;
    }

    /**
     * @param  array<int, string>  $colonnes
     * @return array<string, string>
     */
    private function lireLigne(Row $ligne, array $colonnes): array
    {
        $valeurs = [];

        foreach ($ligne->getCells() as $index => $cellule) {
            if (! isset($colonnes[$index])) {
                continue;
            }

            $valeurs[$colonnes[$index]] = trim($this->texteCellule($cellule));
        }

        return $valeurs;
    }

    /**
     * @param  array<string, string>  $ligne
     */
    private function resoudreInfos(array $ligne, array &$avertissements): array
    {
        $donnees = array_filter($ligne, fn ($v) => $v !== '');

        foreach (['date_emission', 'date_debut', 'date_fin'] as $champDate) {
            if (! empty($donnees[$champDate])) {
                $date = $this->parserDate($donnees[$champDate]);

                if (! $date) {
                    $colonne = $this->libelleColonne(self::TABLEAU_INFOS, $champDate);
                    $avertissements[] = "colonne « {$colonne} » : date « {$donnees[$champDate]} » illisible (format attendu : jj/mm/aaaa).";
                }

                $donnees[$champDate] = $date?->toDateString();
            }
        }

        // Le tableau "Informations générales" indique la casse exacte
        // attendue ("brouillon, emise, envoyee, cloturee") mais un
        // utilisateur qui remplit le document tape naturellement "Émise" /
        // "Envoyée" / "Clôturée" — sans normalisation, StoreConvocationRequest
        // (règle "in:brouillon,emise,envoyee,cloturee", sensible à la casse
        // et aux accents) rejetait toute la convocation, statut compris.
        if (isset($donnees['statut'])) {
            $statut = $this->normaliserStatut($donnees['statut']);

            if ($statut) {
                $donnees['statut'] = $statut;
            } else {
                $avertissements[] = "colonne « Statut de la convocation » : valeur « {$donnees['statut']} » non reconnue (attendu : brouillon, émise, envoyée ou clôturée) — laissé au défaut « brouillon ».";
                unset($donnees['statut']);
            }
        }

        return $donnees;
    }

    private function normaliserStatut(string $valeur): ?string
    {
        $normalise = $this->normaliser($valeur);

        return match (true) {
            str_starts_with($normalise, 'brouillon') => 'brouillon',
            str_starts_with($normalise, 'emise') => 'emise',
            str_starts_with($normalise, 'envoyee') => 'envoyee',
            str_starts_with($normalise, 'cloturee') => 'cloturee',
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $ligne
     */
    private function resoudreCentre(array $ligne, array &$avertissements): ?array
    {
        if (empty($ligne['centre'])) {
            return null;
        }

        $chef = $this->resoudrePersonneCentre($ligne, 'chef_centre', 'chef de centre', $avertissements);
        $president = $this->resoudrePersonneCentre($ligne, 'president_jury', 'président du jury', $avertissements);

        return [
            'centre' => $ligne['centre'],
            'jury' => $ligne['jury'] ?: null,
            'metier' => $ligne['metier'] ?: null,
            'chef_centre_id' => $chef['id'],
            'chef_centre_telephone' => $chef['telephone'],
            'chef_centre_provenance' => $chef['provenance'],
            'chef_centre_categorie_personnel' => $chef['categorie_personnel'],
            'president_jury_id' => $president['id'],
            'president_jury_telephone' => $president['telephone'],
            'president_jury_provenance' => $president['provenance'],
            'president_jury_categorie_personnel' => $president['categorie_personnel'],
        ];
    }

    /**
     * Chef de centre ou président du jury d'une ligne du tableau Centres :
     * retrouvé par matricule (ou par nom complet pour un ancien modèle), puis
     * complété avec son téléphone, sa provenance et sa catégorie en base.
     *
     * @param  array<string, string>  $ligne
     * @return array{id: ?int, telephone: ?string, provenance: ?string, categorie_personnel: ?string}
     */
    private function resoudrePersonneCentre(array $ligne, string $prefixe, string $libelle, array &$avertissements): array
    {
        $matricule = $ligne["{$prefixe}_matricule"] ?? '';
        $nom = $ligne[$prefixe] ?? '';
        $enseignant = null;

        if ($matricule !== '') {
            $enseignant = $this->trouverEnseignantParMatricule($matricule);

            if (! $enseignant) {
                $colonne = $this->libelleColonne(self::TABLEAU_CENTRES, "{$prefixe}_matricule");
                $avertissements[] = "colonne « {$colonne} » : matricule « {$matricule} » introuvable dans la base — {$libelle} laissé vide.";
            }
        } elseif ($nom !== '') {
            $enseignant = $this->trouverEnseignantParNom($nom);

            if (! $enseignant) {
                $colonne = $this->libelleColonne(self::TABLEAU_CENTRES, $prefixe);
                $avertissements[] = "colonne « {$colonne} » : « {$nom} » non reconnu (aucun ou plusieurs enseignants portent ce nom) — {$libelle} laissé vide.";
            }
        }

        $categorie = $this->categorieSaisie(
            $ligne["{$prefixe}_categorie_personnel"] ?? '',
            $this->libelleColonne(self::TABLEAU_CENTRES, "{$prefixe}_categorie_personnel"),
            $avertissements
        );

        return [
            'id' => $enseignant?->id,
            'telephone' => ($ligne["{$prefixe}_telephone"] ?? '') ?: $enseignant?->telephone,
            'provenance' => ($ligne["{$prefixe}_provenance"] ?? '') ?: $this->provenance($enseignant),
            'categorie_personnel' => $categorie ?? $this->categorieEnseignant($enseignant),
        ];
    }

    /**
     * @param  array<string, string>  $ligne
     */
    private function resoudreMembre(array $ligne, array &$avertissements): ?array
    {
        $matricule = $ligne['matricule'] ?? '';
        $nomComplet = trim(($ligne['prenom'] ?? '').' '.($ligne['nom'] ?? ''));

        if ($matricule !== '') {
            $enseignant = $this->trouverEnseignantParMatricule($matricule);
            $erreur = "colonne « Matricule » : matricule « {$matricule} » introuvable dans la base";
        } elseif ($nomComplet !== '') {
            // Ancien modèle (Prénom / Nom).
            $enseignant = $this->trouverEnseignantParNom($nomComplet);
            $erreur = "colonnes « Prénom » / « Nom » : « {$nomComplet} » non reconnu (aucun ou plusieurs enseignants portent ce nom)";
        } else {
            $avertissements[] = 'colonne « Matricule » vide — membre ignoré.';

            return null;
        }

        if (! $enseignant) {
            $avertissements[] = "{$erreur} — membre ignoré.";

            return null;
        }

        $categorie = $this->categorieSaisie($ligne['categorie_personnel'] ?? '', 'Catégorie de personnel', $avertissements);

        return [
            'enseignant_id' => $enseignant->id,
            'centre_nom' => $ligne['centre_nom'] ?: null,
            'fonction' => $ligne['fonction'] ?: null,
            'categorie_personnel' => $categorie ?? $this->categorieEnseignant($enseignant),
            'provenance' => ($ligne['provenance'] ?? '') ?: $this->provenance($enseignant),
        ];
    }

    /**
     * Catégorie saisie dans le document (anciens modèles uniquement).
     */
    private function categorieSaisie(string $valeur, string $contexte, array &$avertissements): ?string
    {
        if ($valeur === '') {
            return null;
        }

        $categorie = $this->normaliserCategoriePersonnel($valeur);

        if (! $categorie) {
            $avertissements[] = "colonne « {$contexte} » : valeur « {$valeur} » non reconnue (attendu : Fonctionnaire, Contractuelle ou Vacataire) — catégorie reprise de la base.";
        }

        return $categorie;
    }

    /**
     * Catégorie de personnel en base : colonne enseignants.categorie_personnel,
     * à défaut déduite du statut (même logique que
     * ConvocationEnseignantMatriculeController pour la saisie manuelle).
     */
    private function categorieEnseignant(?Enseignant $enseignant): ?string
    {
        if (! $enseignant) {
            return null;
        }

        $statut = $enseignant->statutEnseignant;

        foreach ([$enseignant->categorie_personnel, $statut?->code, $statut?->categorie, $statut?->libelle] as $valeur) {
            if ($valeur && ($categorie = $this->normaliserCategoriePersonnel((string) $valeur))) {
                return $categorie;
            }
        }

        return null;
    }

    /**
     * Provenance (lieu où l'enseignant exerce habituellement) : son lieu de service.
     */
    private function provenance(?Enseignant $enseignant): ?string
    {
        return $enseignant?->lieuService?->libelle ?: null;
    }

    private function trouverEnseignantParMatricule(string $matricule): ?Enseignant
    {
        $resultats = Enseignant::with(['statutEnseignant', 'lieuService'])
            ->whereRaw('LOWER(TRIM(matricule)) = ?', [Str::lower(trim($matricule))])
            ->get();

        return $resultats->count() === 1 ? $resultats->first() : null;
    }

    /**
     * Fait correspondre le texte libre de la colonne "Catégorie de
     * personnel" (Fonctionnaire / Contractuelle / Vacataire, cf.
     * CHAMPS_MEMBRE) à l'une des valeurs de l'enum
     * convocation_enseignant.categorie_personnel (fonctionnaire /
     * contractuel / vacataire — sans le "le" final de "contractuelle").
     */
    private function normaliserCategoriePersonnel(string $valeur): ?string
    {
        $normalise = $this->normaliser($valeur);

        return match (true) {
            str_starts_with($normalise, 'fonctionnaire') => 'fonctionnaire',
            str_starts_with($normalise, 'contractuel') => 'contractuel',
            str_starts_with($normalise, 'vacataire') => 'vacataire',
            default => null,
        };
    }

    private function texteCellule(Cell $cell): string
    {
        $texte = '';

        foreach ($cell->getElements() as $element) {
            $texte .= $this->texteElement($element);
        }

        return trim($texte);
    }

    private function texteElement(mixed $element): string
    {
        if (method_exists($element, 'getText')) {
            
            return html_entity_decode((string) $element->getText(), ENT_QUOTES | ENT_XML1);
        }

        if (method_exists($element, 'getElements')) {
            $texte = '';

            foreach ($element->getElements() as $sousElement) {
                $texte .= $this->texteElement($sousElement);
            }

            return $texte.' ';
        }

        return '';
    }

    private function normaliser(string $valeur): string
    {
        $valeur = Str::lower(trim($valeur));
        $valeur = Str::ascii($valeur);

        return preg_replace('/[^a-z0-9]/', '', $valeur);
    }

    
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

    private function parserDate(string $valeur): ?Carbon
    {
        $valeur = trim($valeur);

        // jj/mm/aaaa (ou jj-mm-aaaa, jj.mm.aaaa) et aaaa-mm-jj, vérifiés par
        // checkdate() : Carbon::createFromFormat() accepte sinon « 32/13/2026 »
        // en le faisant déborder sur une autre date, sans erreur.
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $valeur, $m)
            || preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $valeur, $iso) && ($m = [0, $iso[3], $iso[2], $iso[1]])) {
            [, $jour, $mois, $annee] = array_map('intval', $m);

            return checkdate($mois, $jour, $annee) ? Carbon::create($annee, $mois, $jour)->startOfDay() : null;
        }

        // Une date Word saisie sous une autre forme numérique (12/2026…) est refusée.
        if (preg_match('#^[\d\s/.-]+$#', $valeur)) {
            return null;
        }

        try {
            return Carbon::parse($valeur);
        } catch (\Throwable) {
            return null;
        }
    }
}
