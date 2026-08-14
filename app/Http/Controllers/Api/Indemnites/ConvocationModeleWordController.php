<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Services\Indemnites\ConvocationWordTemplateService;
use Illuminate\Support\Facades\Storage;

/**
 * Modèle Word téléchargeable pour la création d'une convocation (mêmes
 * champs que le formulaire de saisie manuelle), à remplir puis renvoyer
 * via POST convocations/import (cf. ConvocationImportController).
 */
class ConvocationModeleWordController extends Controller
{
    public function __construct(
        private readonly ConvocationWordTemplateService $modeles
    ) {}

    public function telecharger()
    {
        $chemin = $this->modeles->cheminModele();

        return Storage::disk('public')->download($chemin, 'modele-convocation.docx');
=======
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Modèle Word vierge à remplir pour l'import en masse de convocations
 * (bouton "Télécharger le modèle Word" du modal d'import, cf.
 * ConvocationsController::telechargerModeleWord() côté front, qui relaie
 * ce fichier tel quel). Référencé depuis le début par ce nom de contrôleur
 * dans les commentaires du front, mais jamais créé — c'est pour ça que
 * "Télécharger le modèle" ne renvoyait pas un vrai .docx et qu'aucun
 * fichier importé ne pouvait donc être valide.
 *
 * Une ligne du tableau = une convocation avec UN centre et UN
 * bénéficiaire (même format simple que l'ancien import CSV, cf.
 * ConvocationImportController::ALIAS pour les colonnes reconnues) — pas
 * le format riche (plusieurs centres/métiers/membres) du formulaire
 * "Nouvelle convocation". Pour une convocation avec plusieurs centres ou
 * plusieurs membres du jury, utiliser ce formulaire plutôt que l'import.
 */
class ConvocationModeleWordController extends Controller
{
    /**
     * En-têtes du tableau. Chaque libellé DOIT se normaliser (minuscules,
     * sans accent, sans espace ni ponctuation - cf.
     * ConvocationImportController::normaliserEntete()) vers EXACTEMENT un
     * des alias de ConvocationImportController::ALIAS, donc pas de mot
     * supplémentaire dans une cellule d'en-tête (ex: pas "Lieu d'examen",
     * qui se normalise en "lieudexamen" et ne matche plus rien).
     */
    private const COLONNES = [
        'Matricule',
        'Agent',
        'Type',
        'Session',
        'Centre',
        'Rôle',
        'Provenance',
        'Date début',
        'Date fin',
        'Date émission',
        'Lieu examen',
        'Objet',
    ];

    public function telecharger()
    {
        $document = new PhpWord();

        $section = $document->addSection();

        $section->addText(
            "Modèle d'import de convocations — SICORE",
            ['bold' => true, 'size' => 14]
        );

        $section->addTextBreak();

        $section->addText(
            'Une ligne du tableau ci-dessous = une convocation, avec un centre et un bénéficiaire. '
            .'Pour une convocation avec plusieurs centres, plusieurs métiers ou plusieurs membres du '
            .'jury, utilisez le formulaire "Nouvelle convocation" plutôt que cet import.'
        );

        $section->addTextBreak();

        $section->addText(
            'Colonnes : Matricule (ou Agent = nom complet), Type (libellé ou code exact du type de '
            .'convocation), Session, Centre, Rôle, Provenance, dates au format jj/mm/aaaa, Lieu examen, Objet.'
        );

        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        $table->addRow();

        foreach (self::COLONNES as $colonne) {
            $table->addCell(2200)->addText($colonne, ['bold' => true]);
        }

        // Une ligne vide prête à être remplie - importerLigne() ignore de
        // toute façon les lignes entièrement vides, donc aucun risque
        // qu'elle crée une convocation fantôme si elle n'est pas remplie.
        $table->addRow();

        foreach (self::COLONNES as $colonne) {
            $table->addCell(2200)->addText('');
        }

        $chemin = sys_get_temp_dir().'/modele-convocation-'.uniqid('', true).'.docx';

        IOFactory::createWriter($document, 'Word2007')->save($chemin);

        return response()
            ->download($chemin, 'modele-convocation.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
>>>>>>> 9cfc357 (Convocations)
    }
}
