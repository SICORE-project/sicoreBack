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
    private const CHEMIN_MODELE = 'convocations/modele-convocation.docx';

    private const CHAMPS_INFOS = [
        'Objet' => 'objet',
        'Session' => 'session',
        "Date d'émission (jj/mm/aaaa)" => 'date_emission',
        'Statut de la convocation (brouillon, emise, envoyee, cloturee)' => 'statut',
        'Date début (jj/mm/aaaa)' => 'date_debut',
        'Date fin (jj/mm/aaaa)' => 'date_fin',
        'Heure début (hh:mm)' => 'heure_debut',
        "Lieu d'examen" => 'lieu_examen',
        "Lieu d'affectation" => 'lieu_affectation',
        'Ordre de mission (Oui/Non)' => 'ordre_de_mission',
    ];

    private const CHAMPS_CENTRE = [
        "Centre d'examen" => 'centre',
        'Jury' => 'jury',
        'Métier / spécialité' => 'metier',
        'Chef de centre (nom complet)' => 'chef_centre',
        'Téléphone du chef de centre' => 'chef_centre_telephone',
        // Lieu où le chef de centre / le président du jury exerce
        // habituellement (utilisé par le calcul des frais de déplacement
        // pour l'ajustement ÷4 — même principe que la colonne "Provenance"
        // du tableau Membres du jury ci-dessous).
        'Provenance du chef de centre' => 'chef_centre_provenance',

        'Président du jury (nom complet)' => 'president_jury',
        'Téléphone du président du jury' => 'president_jury_telephone',
        'Provenance du président du jury' => 'president_jury_provenance',
    ];

    private const CHAMPS_MEMBRE = [
        "Centre de rattachement (nom exact d'un centre ci-dessus)" => 'centre_nom',
        'Prénom' => 'prenom',
        'Nom' => 'nom',
        'Fonction (Correction ou Surveillant)' => 'fonction',
        'Catégorie de personnel (Fonctionnaire, Contractuelle, Vacataire)' => 'categorie_personnel',
        'Provenance' => 'provenance',

        'Téléphone' => 'telephone',
    ];

    private const MARQUEUR_INFOS = 'objet';

    private const MARQUEUR_CENTRE = 'centredexamen';

    private const MARQUEUR_MEMBRE = 'prenom';

   
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

        $section->addTitle('Modèle de convocation', 1);
        $section->addText(
            "Remplissez une ligne du tableau « Informations générales » (une seule convocation par document). Ajoutez une ligne par centre d'examen dans le deuxième tableau et une ligne par membre du jury dans le troisième — vous pouvez insérer des lignes supplémentaires si besoin. La colonne « Centre de rattachement » du tableau des membres doit reprendre exactement le nom saisi dans la colonne « Centre d'examen » du tableau des centres."
        );
        $section->addText(
            "Il n'y a plus de type de convocation unique à choisir à l'import : chaque personne a désormais son propre type, déduit automatiquement de son rôle. Dans le tableau « Centres d'examen », le chef de centre et le président du jury donnent respectivement les types « Chef de centre » et « Président de jury ». Dans le tableau « Membres du jury », la colonne « Fonction » (Correction ou Surveillant) donne le type de chaque membre."
        );

        $section->addTextBreak();
        $section->addText('Informations générales', ['bold' => true]);
        $this->ajouterTableau($section, array_keys(self::CHAMPS_INFOS), 10);

        $section->addTextBreak();
        $section->addText("Centres d'examen", ['bold' => true]);
        $this->ajouterTableau($section, array_keys(self::CHAMPS_CENTRE), 2);

        $section->addTextBreak();
        $section->addText('Membres du jury', ['bold' => true]);
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

        $tableInfos = $this->trouverTableau($tables, self::MARQUEUR_INFOS);
        $tableCentre = $this->trouverTableau($tables, self::MARQUEUR_CENTRE);
        $tableMembre = $this->trouverTableau($tables, self::MARQUEUR_MEMBRE);

        $convocation = [];
        $centres = [];
        $beneficiaires = [];

        if ($tableInfos) {
            $colonnes = $this->mapperColonnes($tableInfos, self::CHAMPS_INFOS);
            $lignes = $this->lireLignesDonnees($tableInfos, $colonnes);

           
            if (! empty($lignes)) {
                $convocation = $this->resoudreInfos($lignes[0], $avertissements);
            }
        }

        if ($tableCentre) {
            $colonnes = $this->mapperColonnes($tableCentre, self::CHAMPS_CENTRE);

            foreach ($this->lireLignesDonnees($tableCentre, $colonnes) as $ligne) {
                $centre = $this->resoudreCentre($ligne, $avertissements);

                if ($centre) {
                    $centres[] = $centre;
                }
            }
        }

        if ($tableMembre) {
            $colonnes = $this->mapperColonnes($tableMembre, self::CHAMPS_MEMBRE);

            foreach ($this->lireLignesDonnees($tableMembre, $colonnes) as $ligne) {
                $membre = $this->resoudreMembre($ligne, $avertissements);

                if ($membre) {
                    $beneficiaires[] = $membre;
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

  
    private function trouverTableau(array $tables, string $marqueur): ?WordTable
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
    private function lireLignesDonnees(WordTable $table, array $colonnes): array
    {
        $donnees = [];

        foreach (array_slice($table->getRows(), 1) as $ligne) {
            $valeurs = $this->lireLigne($ligne, $colonnes);

            if (count(array_filter($valeurs, fn ($v) => $v !== '')) > 0) {
                $donnees[] = $valeurs;
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
                    $avertissements[] = "« {$champDate} » : date « {$donnees[$champDate]} » illisible.";
                }

                $donnees[$champDate] = $date?->toDateString();
            }
        }

        if (isset($donnees['ordre_de_mission'])) {
            $donnees['ordre_de_mission'] = in_array(
                Str::lower(trim($donnees['ordre_de_mission'])),
                ['oui', '1', 'true', 'o'],
                true
            );
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
                $avertissements[] = "« statut » : valeur « {$donnees['statut']} » non reconnue (attendu : brouillon, émise, envoyée ou clôturée) — laissé au défaut « brouillon ».";
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

        $chefCentreId = null;

        if (! empty($ligne['chef_centre'])) {
            $enseignant = $this->trouverEnseignantParNom($ligne['chef_centre']);

            if (! $enseignant) {
                $avertissements[] = "chef de centre « {$ligne['chef_centre']} » (centre « {$ligne['centre']} ») non reconnu.";
            }

            $chefCentreId = $enseignant?->id;
        }

        $presidentJuryId = null;

        if (! empty($ligne['president_jury'])) {
            $enseignant = $this->trouverEnseignantParNom($ligne['president_jury']);

            if (! $enseignant) {
                $avertissements[] = "président du jury « {$ligne['president_jury']} » (centre « {$ligne['centre']} ») non reconnu.";
            }

            $presidentJuryId = $enseignant?->id;
        }

        return [
            'centre' => $ligne['centre'],
            'jury' => $ligne['jury'] ?: null,
            'metier' => $ligne['metier'] ?: null,
            'chef_centre_id' => $chefCentreId,
            'chef_centre_telephone' => $ligne['chef_centre_telephone'] ?: null,
            'chef_centre_provenance' => $ligne['chef_centre_provenance'] ?: null,
            'president_jury_id' => $presidentJuryId,
            'president_jury_telephone' => $ligne['president_jury_telephone'] ?: null,
            'president_jury_provenance' => $ligne['president_jury_provenance'] ?: null,
        ];
    }

    /**
     * @param  array<string, string>  $ligne
     */
    private function resoudreMembre(array $ligne, array &$avertissements): ?array
    {
        $nomComplet = trim(($ligne['prenom'] ?? '').' '.($ligne['nom'] ?? ''));

        if ($nomComplet === '') {
            return null;
        }

        $enseignant = $this->trouverEnseignantParNom($nomComplet);

        if (! $enseignant) {
            $avertissements[] = "membre du jury « {$nomComplet} » non reconnu.";

            return null;
        }

        $categoriePersonnel = null;

        if (! empty($ligne['categorie_personnel'])) {
            $categoriePersonnel = $this->normaliserCategoriePersonnel($ligne['categorie_personnel']);

            if (! $categoriePersonnel) {
                $avertissements[] = "catégorie de personnel « {$ligne['categorie_personnel']} » (membre « {$nomComplet} ») non reconnue — valeurs attendues : Fonctionnaire, Contractuelle, Vacataire.";
            }
        }

        return [
            'enseignant_id' => $enseignant->id,
            'centre_nom' => $ligne['centre_nom'] ?: null,
            'fonction' => $ligne['fonction'] ?: null,
            'categorie_personnel' => $categoriePersonnel,
            'provenance' => $ligne['provenance'] ?: null,
        ];
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
}
