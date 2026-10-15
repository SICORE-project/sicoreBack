<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\EnseignantsController;
use App\Http\Controllers\Api\Indemnites\TypeConvocationController;
use App\Http\Controllers\Api\Indemnites\IndemnitesController;
use App\Http\Controllers\Api\Indemnites\TypeIndemnitesController;
use App\Http\Controllers\Api\Indemnites\ConvocationsController;
use App\Http\Controllers\Api\Indemnites\ConvocationBeneficiaireController;
use App\Http\Controllers\Api\Indemnites\ConvocationCentreController;
use App\Http\Controllers\Api\Indemnites\ConvocationMetierController;
use App\Http\Controllers\Api\Indemnites\ConvocationCentreMetierController;
use App\Http\Controllers\Api\Indemnites\ConvocationSyncController;
use App\Http\Controllers\Api\Indemnites\ConvocationEnvoiController;
use App\Http\Controllers\Api\Indemnites\ConvocationPdfController;
use App\Http\Controllers\Api\Indemnites\ConvocationFichierController;
use App\Http\Controllers\Api\Indemnites\ConvocationImportController;
use App\Http\Controllers\Api\Indemnites\ConvocationModeleWordController;
use App\Http\Controllers\Api\Indemnites\ServicesFaitsController;
use App\Http\Controllers\Api\Indemnites\PieceJustificativesController;
use App\Http\Controllers\Api\Indemnites\AccuseReceptionController;
use App\Http\Controllers\Api\Indemnites\FraisDeplacementController;
use App\Http\Controllers\Api\Indemnites\FraisDeplacementPdfController;
use App\Http\Controllers\Api\Indemnites\IndemniteCorrectionController;
use App\Http\Controllers\Api\Indemnites\IndemniteSurveillanceController;
use App\Http\Controllers\Api\Indemnites\EtatPaieIndemnitesController;
use App\Http\Controllers\Api\Indemnites\BoursesController;
use App\Http\Controllers\Api\Indemnites\AidesEtudiantesController;

/*
|--------------------------------------------------------------------------
| Indemnités
|--------------------------------------------------------------------------
*/

Route::apiResource('indemnites', IndemnitesController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('indemnites', IndemnitesController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');
Route::apiResource('type-indemnites', TypeIndemnitesController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('type-indemnites', TypeIndemnitesController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('indemnites/calculer', [IndemnitesController::class, 'calculer'])->middleware('permission:indemnites.manage');
Route::post('indemnites/simuler', [IndemnitesController::class, 'simuler'])->middleware('permission:indemnites.read');
Route::post('indemnites/valider-calcul', [IndemnitesController::class, 'validerCalcul'])->middleware('permission:indemnites.validate');

Route::post('indemnites/{id}/frais', [IndemnitesController::class, 'ajouterFrais'])->middleware('permission:indemnites.manage');
Route::get('indemnites/{id}/frais', [IndemnitesController::class, 'listeFrais'])->middleware('permission:indemnites.read');


/*
|--------------------------------------------------------------------------
| Enseignants (recherche minimale, utilisee par le formulaire convocations)
|--------------------------------------------------------------------------
*/

Route::get('enseignants/coordonnees-bancaires', [EnseignantsController::class, 'coordonneesBancaires'])->middleware('permission:indemnites.read');
Route::get('enseignants', [EnseignantsController::class, 'index'])->middleware('permission:convocations.read,indemnites.read');


/*
|--------------------------------------------------------------------------
| Types de convocation (paramétrage minimal, utilisé par le select du
| formulaire de création de convocation)
|--------------------------------------------------------------------------
*/

Route::get('types-convocation', [TypeConvocationController::class, 'index'])->middleware('permission:convocations.read,indemnites.read');


/*
|--------------------------------------------------------------------------
| Convocations
|--------------------------------------------------------------------------
*/

// Routes à segment fixe déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET convocations/{id}) — modèle Word
// téléchargeable puis import du document rempli (une convocation par
// document, avec ses centres et ses membres du jury).
Route::get('convocations/modele-word', [ConvocationModeleWordController::class, 'telecharger'])->middleware('permission:convocations.read,indemnites.read');
Route::post('convocations/import', [ConvocationImportController::class, 'store'])->middleware('permission:convocations.manage,indemnites.manage');

// Valeurs distinctes deja utilisees (objet/session/centre), pour peupler
// des menus deroulants de filtre (ex: page Pieces justificatives) plutot
// que de la saisie libre.
Route::get('convocations/filtres-options', [ConvocationsController::class, 'optionsFiltres'])->middleware('permission:convocations.read,indemnites.read');

Route::apiResource('convocations', ConvocationsController::class)->only(['index', 'show'])->middleware('permission:convocations.read,indemnites.read');
Route::apiResource('convocations', ConvocationsController::class)->except(['index', 'show'])->middleware('permission:convocations.manage,indemnites.manage');
Route::post('convocations/{id}/fichier', [ConvocationFichierController::class, 'store'])->middleware('permission:convocations.manage,indemnites.manage');


Route::put('convocations/{id}/structure', [ConvocationSyncController::class, 'sync'])->middleware('permission:convocations.manage,indemnites.manage');


Route::get('convocations/{id}/beneficiaires', [ConvocationBeneficiaireController::class, 'index'])->middleware('permission:convocations.read,indemnites.read');
Route::post('convocations/{id}/beneficiaires', [ConvocationBeneficiaireController::class, 'store'])->middleware('permission:convocations.manage,indemnites.manage');
Route::put('convocations/{id}/beneficiaires/{enseignantId}', [ConvocationBeneficiaireController::class, 'update'])->middleware('permission:convocations.manage,indemnites.manage');
Route::delete('convocations/{id}/beneficiaires/{enseignantId}', [ConvocationBeneficiaireController::class, 'destroy'])->middleware('permission:convocations.manage,indemnites.manage');

Route::get('convocations/{id}/centres', [ConvocationCentreController::class, 'index'])->middleware('permission:convocations.read,indemnites.read');
Route::post('convocations/{id}/centres', [ConvocationCentreController::class, 'store'])->middleware('permission:convocations.manage,indemnites.manage');
Route::put('convocations/{id}/centres/{centreId}', [ConvocationCentreController::class, 'update'])->middleware('permission:convocations.manage,indemnites.manage');
Route::delete('convocations/{id}/centres/{centreId}', [ConvocationCentreController::class, 'destroy'])->middleware('permission:convocations.manage,indemnites.manage');

Route::post('convocations/{id}/centres/{centreId}/metiers', [ConvocationMetierController::class, 'store'])->middleware('permission:convocations.manage,indemnites.manage');
Route::put('convocations/{id}/centres/{centreId}/metiers/{metierId}', [ConvocationMetierController::class, 'update'])->middleware('permission:convocations.manage,indemnites.manage');
Route::delete('convocations/{id}/centres/{centreId}/metiers/{metierId}', [ConvocationMetierController::class, 'destroy'])->middleware('permission:convocations.manage,indemnites.manage');

Route::get('convocations/{id}/pdf', [ConvocationPdfController::class, 'generer'])->middleware('permission:convocations.read,indemnites.read');
Route::get('convocations/{id}/download', [ConvocationPdfController::class, 'download'])->middleware('permission:convocations.read,indemnites.read');

Route::post('convocations/{id}/envoyer', [ConvocationEnvoiController::class, 'envoyer'])->middleware('permission:convocations.manage,indemnites.manage');
Route::post('convocations/{id}/relancer', [ConvocationEnvoiController::class, 'relancer'])->middleware('permission:convocations.manage,indemnites.manage');

Route::get('convocations/{id}/suivi', [ConvocationEnvoiController::class, 'suivi'])->middleware('permission:convocations.read,indemnites.read');


/*
|--------------------------------------------------------------------------
| Services faits
|--------------------------------------------------------------------------
*/

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET services-faits/{id})
Route::get('services-faits/rechercher', [ServicesFaitsController::class, 'rechercher'])->middleware('permission:indemnites.read');
Route::get('services-faits/export', [ServicesFaitsController::class, 'export'])->middleware('permission:indemnites.read');

Route::apiResource('services-faits', ServicesFaitsController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('services-faits', ServicesFaitsController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('services-faits/{id}/valider', [ServicesFaitsController::class, 'valider'])->middleware('permission:indemnites.validate');
Route::post('services-faits/{id}/rejeter', [ServicesFaitsController::class, 'rejeter'])->middleware('permission:indemnites.validate');
Route::post('services-faits/{id}/corriger', [ServicesFaitsController::class, 'corriger'])->middleware('permission:indemnites.validate');

Route::get('services-faits/{id}/historique', [ServicesFaitsController::class, 'historique'])->middleware('permission:indemnites.read');

Route::post('services-faits/{id}/verifier-conformite', [ServicesFaitsController::class, 'verifierConformite'])->middleware('permission:indemnites.validate');


/*
|--------------------------------------------------------------------------
| Pièces justificatives
|--------------------------------------------------------------------------
*/

Route::apiResource('pieces-justificatives', PieceJustificativesController::class)->only(['index', 'show'])->middleware('permission:pieces.read,indemnites.read');
Route::apiResource('pieces-justificatives', PieceJustificativesController::class)->except(['index', 'show'])->middleware('permission:pieces.manage,indemnites.manage');

Route::post('pieces-justificatives/deposer', [PieceJustificativesController::class, 'deposer'])->middleware('permission:pieces.manage,indemnites.manage');
Route::post('pieces-justificatives/deposer-lot', [PieceJustificativesController::class, 'deposerLot'])->middleware('permission:pieces.manage,indemnites.manage');

Route::put('pieces-justificatives/{id}/classifier', [PieceJustificativesController::class, 'classifier'])->middleware('permission:pieces.manage,indemnites.manage');

Route::post('pieces-justificatives/{id}/verifier', [PieceJustificativesController::class, 'verifier'])->middleware('permission:pieces.validate,indemnites.validate');
Route::post('pieces-justificatives/{id}/valider', [PieceJustificativesController::class, 'valider'])->middleware('permission:pieces.validate,indemnites.validate');
Route::post('pieces-justificatives/{id}/rejeter', [PieceJustificativesController::class, 'rejeter'])->middleware('permission:pieces.validate,indemnites.validate');

Route::get('pieces-justificatives/{id}/download', [PieceJustificativesController::class, 'download'])->middleware('permission:pieces.read,indemnites.read');
Route::post('pieces-justificatives/{id}/notifier', [PieceJustificativesController::class, 'notifier'])->middleware('permission:pieces.manage,indemnites.manage');


/*
|--------------------------------------------------------------------------
| Accusés de réception
|--------------------------------------------------------------------------
*/

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET accuses-reception/{id})
Route::get('accuses-reception/modeles', [AccuseReceptionController::class, 'modeles'])->middleware('permission:indemnites.read');
Route::get('accuses-reception/politique-archivage', [AccuseReceptionController::class, 'politiqueArchivage'])->middleware('permission:indemnites.read');

Route::apiResource('accuses-reception', AccuseReceptionController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('accuses-reception', AccuseReceptionController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::get('accuses-reception/{id}/generer', [AccuseReceptionController::class, 'generer'])->middleware('permission:indemnites.read');
Route::post('accuses-reception/{id}/signer', [AccuseReceptionController::class, 'signer'])->middleware('permission:indemnites.validate');

Route::get('accuses-reception/{id}/export', [AccuseReceptionController::class, 'export'])->middleware('permission:indemnites.read');
Route::post('accuses-reception/{id}/archiver', [AccuseReceptionController::class, 'archiver'])->middleware('permission:indemnites.validate');

Route::get('accuses-reception/{id}/consulter', [AccuseReceptionController::class, 'consulter'])->middleware('permission:indemnites.read');


/*
|--------------------------------------------------------------------------
| Frais de déplacement
|--------------------------------------------------------------------------
*/

// Route à segment fixe déclarée AVANT apiResource() pour ne pas être
// interceptée par la route "show" (GET frais-deplacement/{id}) — liste des
// bénéficiaires d'une convocation dont le dossier de pièces justificatives
// est complet (seuls ceux-là peuvent recevoir une fiche de déplacement).
Route::get('frais-deplacement/beneficiaires-eligibles-lot', [FraisDeplacementController::class, 'beneficiairesEligiblesLot'])->middleware('permission:indemnites.read');
Route::get('frais-deplacement/beneficiaires-eligibles', [FraisDeplacementController::class, 'beneficiairesEligibles'])->middleware('permission:indemnites.read');

Route::apiResource('frais-deplacement', FraisDeplacementController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('frais-deplacement', FraisDeplacementController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('frais-deplacement/{id}/calculer', [FraisDeplacementController::class, 'calculer'])->middleware('permission:indemnites.manage');

Route::get('frais-deplacement/{id}/justificatifs', [FraisDeplacementController::class, 'justificatifs'])->middleware('permission:indemnites.read');
Route::post('frais-deplacement/{id}/justificatifs', [FraisDeplacementController::class, 'deposerJustificatif'])->middleware('permission:indemnites.manage');
Route::get('frais-deplacement/{id}/justificatifs/{justificatifId}/download', [FraisDeplacementController::class, 'downloadJustificatif'])->middleware('permission:indemnites.read');
Route::delete('frais-deplacement/{id}/justificatifs/{justificatifId}', [FraisDeplacementController::class, 'supprimerJustificatif'])->middleware('permission:indemnites.manage');

Route::post('frais-deplacement/{id}/valider', [FraisDeplacementController::class, 'valider'])->middleware('permission:indemnites.validate');
Route::post('frais-deplacement/{id}/rejeter', [FraisDeplacementController::class, 'rejeter'])->middleware('permission:indemnites.validate');

Route::post('frais-deplacement/{id}/rembourser', [FraisDeplacementController::class, 'rembourser'])->middleware('permission:indemnites.validate');
Route::post('frais-deplacement/{id}/relancer', [FraisDeplacementController::class, 'relancer'])->middleware('permission:indemnites.manage');
Route::post('frais-deplacement/{id}/cloturer', [FraisDeplacementController::class, 'cloturer'])->middleware('permission:indemnites.validate');

// Téléchargement de la fiche en PDF (demande utilisatrice : "je veux
// pouvoir télécharger la fiche si possible").
Route::get('frais-deplacement/{id}/pdf', [FraisDeplacementPdfController::class, 'generer'])->middleware('permission:indemnites.read');
Route::get('frais-deplacement/{id}/download', [FraisDeplacementPdfController::class, 'download'])->middleware('permission:indemnites.read');


/*
|--------------------------------------------------------------------------
| Indemnités de correction
|--------------------------------------------------------------------------
*/

Route::get('indemnite-correction/correcteurs-eligibles-lot', [IndemniteCorrectionController::class, 'correcteursEligiblesLot'])->middleware('permission:indemnites.read');
Route::get('indemnite-correction/correcteurs-eligibles', [IndemniteCorrectionController::class, 'correcteursEligibles'])->middleware('permission:indemnites.read');
Route::post('indemnite-correction/calculer-groupe', [IndemniteCorrectionController::class, 'calculerGroupe'])->middleware('permission:indemnites.manage');


/*
|--------------------------------------------------------------------------
| Indemnités de surveillance
|--------------------------------------------------------------------------
*/

Route::get('indemnite-surveillance/surveillants-eligibles-lot', [IndemniteSurveillanceController::class, 'surveillantsEligiblesLot'])->middleware('permission:indemnites.read');
Route::get('indemnite-surveillance/surveillants-eligibles', [IndemniteSurveillanceController::class, 'surveillantsEligibles'])->middleware('permission:indemnites.read');
Route::post('indemnite-surveillance/calculer-groupe', [IndemniteSurveillanceController::class, 'calculerGroupe'])->middleware('permission:indemnites.manage');


/*
|--------------------------------------------------------------------------
| États de paie des indemnités
|--------------------------------------------------------------------------
*/

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET etat-paie-indemnites/{id})
Route::post('etat-paie-indemnites/fiche-pdf', [EtatPaieIndemnitesController::class, 'fichePdf'])->middleware('permission:indemnites.read');
Route::get('etat-paie-indemnites/individuels', [EtatPaieIndemnitesController::class, 'individuels'])->middleware('permission:indemnites.read');
Route::get('etat-paie-indemnites/consolides', [EtatPaieIndemnitesController::class, 'consolides'])->middleware('permission:indemnites.read');
Route::get('etat-paie-indemnites/export', [EtatPaieIndemnitesController::class, 'export'])->middleware('permission:indemnites.read');
Route::get('etat-paie-indemnites/historique', [EtatPaieIndemnitesController::class, 'historique'])->middleware('permission:indemnites.read');

Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('etat-paie-indemnites/{id}/generer', [EtatPaieIndemnitesController::class, 'generer'])->middleware('permission:indemnites.manage');
Route::get('etat-paie-indemnites/{id}/preview', [EtatPaieIndemnitesController::class, 'preview'])->middleware('permission:indemnites.read');

Route::post('etat-paie-indemnites/{id}/valider', [EtatPaieIndemnitesController::class, 'valider'])->middleware('permission:indemnites.validate');
Route::post('etat-paie-indemnites/{id}/archiver', [EtatPaieIndemnitesController::class, 'archiver'])->middleware('permission:indemnites.validate');
Route::post('etat-paie-indemnites/{id}/transmettre', [EtatPaieIndemnitesController::class, 'transmettre'])->middleware('permission:indemnites.manage');


/*
|--------------------------------------------------------------------------
| Bourses et aides étudiantes
|--------------------------------------------------------------------------
*/


Route::apiResource('bourses', BoursesController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('bourses', BoursesController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('bourses/{id}/valider', [BoursesController::class, 'valider'])->middleware('permission:indemnites.validate');
Route::post('bourses/{id}/rejeter', [BoursesController::class, 'rejeter'])->middleware('permission:indemnites.validate');

Route::post('bourses/{id}/calculer-montant', [BoursesController::class, 'calculerMontant'])->middleware('permission:indemnites.manage');

Route::get('bourses/{id}/pieces', [BoursesController::class, 'pieces'])->middleware('permission:indemnites.read');
Route::post('bourses/{id}/pieces', [BoursesController::class, 'deposerPiece'])->middleware('permission:indemnites.manage');

Route::post('bourses/{id}/archiver', [BoursesController::class, 'archiver'])->middleware('permission:indemnites.validate');


Route::apiResource('aides-etudiantes', AidesEtudiantesController::class)->only(['index', 'show'])->middleware('permission:indemnites.read');
Route::apiResource('aides-etudiantes', AidesEtudiantesController::class)->except(['index', 'show'])->middleware('permission:indemnites.manage');

Route::post('aides-etudiantes/{id}/valider', [AidesEtudiantesController::class, 'valider'])->middleware('permission:indemnites.validate');
Route::post('aides-etudiantes/{id}/rejeter', [AidesEtudiantesController::class, 'rejeter'])->middleware('permission:indemnites.validate');

Route::post('aides-etudiantes/{id}/calculer-montant', [AidesEtudiantesController::class, 'calculerMontant'])->middleware('permission:indemnites.manage');

Route::get('aides-etudiantes/{id}/pieces', [AidesEtudiantesController::class, 'pieces'])->middleware('permission:indemnites.read');
Route::post('aides-etudiantes/{id}/pieces', [AidesEtudiantesController::class, 'deposerPiece'])->middleware('permission:indemnites.manage');

Route::post('aides-etudiantes/{id}/archiver', [AidesEtudiantesController::class, 'archiver'])->middleware('permission:indemnites.validate');