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

/**
 * Génère le modèle Word téléchargeable pour la création d'une convocation
 * (mêmes champs que le formulaire de saisie manuelle, cf.
 * resources/views/pages/indemnites/convocations/create.blade.php côté
 * sicoreFront) et lit un modèle rempli pour en extraire les données.
 *
 * Un seul document = une seule convocation (avec ses centres et ses
 * membres du jury qui lui sont rattachés) : seule la 1re ligne de données
 * du tableau "Informations générales" est utilisée si plusieurs sont
 * remplies ; en revanche TOUTES les lignes remplies des tableaux Centres
 * et Membres sont prises en compte (plusieurs centres/membres par
 * convocation).
 *
 * Chaque tableau a une ligne d'en-tête (les champs, en colonnes) suivie de
 * lignes de données : 10 colonnes × 11 lignes pour les informations
 * générales, 5 colonnes × 3 lignes pour les centres d'examen, 6 colonnes ×
 * 3 lignes pour les membres du jury. L'utilisateur peut ajouter des lignes
 * supplémentaires dans Word (centres/membres) si besoin.
 *
 * Le type de convocation n'est PAS un champ du document : il est choisi
 * dans le formulaire d'import (modal) à côté du fichier, et transmis
 * séparément — voir ImportConvocationsRequest::rules() côté API et
 * ConvocationsController::import() côté sicoreFront.
 */
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
        // Contact academique du centre, distinct du chef de centre
        // (contact administratif) — voir convocation_centres.president_jury_id.
        'Président du jury (nom complet)' => 'president_jury',
        'Téléphone du président du jury' => 'president_jury_telephone',
    ];

    private const CHAMPS_MEMBRE = [
        "Centre de rattachement (nom exact d'un centre ci-dessus)" => 'centre_nom',
        'Prénom' => 'prenom',
        'Nom' => 'nom',
        'Fonction' => 'fonction',
        'Catégorie de personnel (Fonctionnaire, Contractuelle, Vacataire)' => 'categorie_personnel',
        'Provenance' => 'provenance',
        // Informatif (ex: contacter le President de jury) : la table pivot
        // convocation_enseignant n'a pas de colonne telephone (seulement
        // fonction/centre_id/provenance), donc cette valeur n'est pas
        // persistee — voir resoudreMembre(), qui l'ignore volontairement.
        'Téléphone' => 'telephone',
    ];

    /** Marqueur (libellé de colonne) qui identifie chaque type de tableau à la lecture. */
    private const MARQUEUR_INFOS = 'objet';

    private const MARQUEUR_CENTRE = 'centredexamen';

    private const MARQUEUR_MEMBRE = 'prenom';

    /**
     * Chemin (sur le disque `public`) du modèle téléchargeable, généré une
     * seule fois puis mis en cache — même logique paresseuse que
     * ConvocationPdfController::download().
     */
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

    /**
     * Ajoute un tableau "large" : une ligne d'en-tête (les libellés
     * fournis, un par colonne) suivie de $lignesVides lignes de données
     * vides à remplir.
     */
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

    /**
     * Lit un .docx rempli et retourne les données prêtes à être validées
     * par StoreConvocationRequest / StoreConvocationCentresRequest /
     * AttachBeneficiairesConvocationRequest. Les entités référencées par
     * leur nom (chef de centre, agent) sont déjà résolues en id ici ; seul
     * le rattachement centre<->membre (par nom)
     * reste à faire côté appelant, une fois les centres persistés.
     */
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

            // Une seule convocation par document : seule la 1re ligne
            // remplie compte, les suivantes ne servent qu'à l'espacement.
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

    /**
     * Retrouve, parmi les tableaux du document, celui dont la ligne
     * d'en-tête contient une colonne correspondant au marqueur donné —
     * indépendant de la position/l'ordre des tableaux dans le document.
     */
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

    /**
     * Associe chaque index de colonne de la ligne d'en-tête au nom de
     * champ interne correspondant (via $champs, libellé => champ interne),
     * par correspondance "contient" une fois normalisé — les libellés les
     * plus longs sont testés en premier pour éviter qu'un libellé court
     * ("Nom") ne matche à tort une colonne plus longue qui le contient
     * ("Prénom").
     *
     * @return array<int, string> index de colonne => champ interne
     */
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

        return $donnees;
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
            'president_jury_id' => $presidentJuryId,
            'president_jury_telephone' => $ligne['president_jury_telephone'] ?: null,
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

        return [
            'enseignant_id' => $enseignant->id,
            'centre_nom' => $ligne['centre_nom'] ?: null,
            'fonction' => $ligne['fonction'] ?: null,
            'categorie_personnel' => $ligne['categorie_personnel'] ?: null,
            'provenance' => $ligne['provenance'] ?: null,
        ];
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
            // Le lecteur PhpWord renvoie le texte encode en entites HTML
            // (ex: apostrophe -> "&#039;") au lieu du caractere brut : sans
            // ce decodage, toute comparaison ("d'examen", noms avec
            // apostrophe...) echoue silencieusement.
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

    /**
     * Recherche un enseignant par nom complet, dans les deux ordres
     * possibles ("Prénom Nom" et "Nom Prénom") — même logique que
     * l'ancien import CSV. Une ambiguïté (plusieurs correspondances) n'est
     * pas résolue automatiquement.
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
