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
use App\Http\Controllers\Api\Indemnites\NotificationController;


/*
|--------------------------------------------------------------------------
| Indemnités
|--------------------------------------------------------------------------
*/

Route::get('/indemnites/notifications', [NotificationController::class, 'index']);
Route::post('/indemnites/notifications', [NotificationController::class, 'store']);
Route::patch('/indemnites/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
Route::get('/indemnites/notifications/unread-count', [NotificationController::class, 'unreadCount']);
Route::patch('/indemnites/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

/*
|--------------------------------------------------------------------------
| Indemnités
|--------------------------------------------------------------------------
*/

Route::apiResource('indemnites', IndemnitesController::class);
Route::apiResource('type-indemnites', TypeIndemnitesController::class);

Route::post('indemnites/calculer', [IndemnitesController::class, 'calculer']);
Route::post('indemnites/simuler', [IndemnitesController::class, 'simuler']);
Route::post('indemnites/valider-calcul', [IndemnitesController::class, 'validerCalcul']);

Route::post('indemnites/{id}/frais', [IndemnitesController::class, 'ajouterFrais']);
Route::get('indemnites/{id}/frais', [IndemnitesController::class, 'listeFrais']);


/*
|--------------------------------------------------------------------------
| Enseignants (recherche minimale, utilisee par le formulaire convocations)
|--------------------------------------------------------------------------
*/

Route::get('enseignants', [EnseignantsController::class, 'index']);
Route::get('enseignants/{id}', [EnseignantsController::class, 'show']);


/*
|--------------------------------------------------------------------------
| Types de convocation (paramétrage minimal, utilisé par le select du
| formulaire de création de convocation)
|--------------------------------------------------------------------------
*/

Route::get('types-convocation', [TypeConvocationController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Convocations
|--------------------------------------------------------------------------
*/

// Routes à segment fixe déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET convocations/{id}) — modèle Word
// téléchargeable puis import du document rempli (une convocation par
// document, avec ses centres et ses membres du jury).
Route::get('convocations/modele-word', [ConvocationModeleWordController::class, 'telecharger']);
Route::post('convocations/import', [ConvocationImportController::class, 'store']);

// Valeurs distinctes deja utilisees (objet/session/centre), pour peupler
// des menus deroulants de filtre (ex: page Pieces justificatives) plutot
// que de la saisie libre.
Route::get('convocations/filtres-options', [ConvocationsController::class, 'optionsFiltres']);

Route::apiResource('convocations', ConvocationsController::class);
Route::post('convocations/{id}/fichier', [ConvocationFichierController::class, 'store']);


Route::put('convocations/{id}/structure', [ConvocationSyncController::class, 'sync']);


Route::get('convocations/{id}/beneficiaires', [ConvocationBeneficiaireController::class, 'index']);
Route::post('convocations/{id}/beneficiaires', [ConvocationBeneficiaireController::class, 'store']);
Route::put('convocations/{id}/beneficiaires/{enseignantId}', [ConvocationBeneficiaireController::class, 'update']);
Route::delete('convocations/{id}/beneficiaires/{enseignantId}', [ConvocationBeneficiaireController::class, 'destroy']);

Route::get('convocations/{id}/centres', [ConvocationCentreController::class, 'index']);
Route::post('convocations/{id}/centres', [ConvocationCentreController::class, 'store']);
Route::put('convocations/{id}/centres/{centreId}', [ConvocationCentreController::class, 'update']);
Route::delete('convocations/{id}/centres/{centreId}', [ConvocationCentreController::class, 'destroy']);

Route::post('convocations/{id}/centres/{centreId}/metiers', [ConvocationMetierController::class, 'store']);
Route::put('convocations/{id}/centres/{centreId}/metiers/{metierId}', [ConvocationMetierController::class, 'update']);
Route::delete('convocations/{id}/centres/{centreId}/metiers/{metierId}', [ConvocationMetierController::class, 'destroy']);

Route::get('convocations/{id}/pdf', [ConvocationPdfController::class, 'generer']);
Route::get('convocations/{id}/download', [ConvocationPdfController::class, 'download']);

Route::post('convocations/{id}/envoyer', [ConvocationEnvoiController::class, 'envoyer']);
Route::post('convocations/{id}/relancer', [ConvocationEnvoiController::class, 'relancer']);

Route::get('convocations/{id}/suivi', [ConvocationEnvoiController::class, 'suivi']);


/*
|--------------------------------------------------------------------------
| Services faits
|--------------------------------------------------------------------------
*/

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET services-faits/{id})
Route::get('services-faits/rechercher', [ServicesFaitsController::class, 'rechercher']);
Route::get('services-faits/export', [ServicesFaitsController::class, 'export']);

Route::apiResource('services-faits', ServicesFaitsController::class);

Route::post('services-faits/{id}/valider', [ServicesFaitsController::class, 'valider']);
Route::post('services-faits/{id}/rejeter', [ServicesFaitsController::class, 'rejeter']);
Route::post('services-faits/{id}/corriger', [ServicesFaitsController::class, 'corriger']);

Route::get('services-faits/{id}/historique', [ServicesFaitsController::class, 'historique']);

Route::post('services-faits/{id}/verifier-conformite', [ServicesFaitsController::class, 'verifierConformite']);


/*
|--------------------------------------------------------------------------
| Pièces justificatives
|--------------------------------------------------------------------------
*/

Route::apiResource('pieces-justificatives', PieceJustificativesController::class);

Route::post('pieces-justificatives/deposer', [PieceJustificativesController::class, 'deposer']);
Route::post('pieces-justificatives/deposer-lot', [PieceJustificativesController::class, 'deposerLot']);

Route::put('pieces-justificatives/{id}/classifier', [PieceJustificativesController::class, 'classifier']);

Route::post('pieces-justificatives/{id}/verifier', [PieceJustificativesController::class, 'verifier']);
Route::post('pieces-justificatives/{id}/valider', [PieceJustificativesController::class, 'valider']);
Route::post('pieces-justificatives/{id}/rejeter', [PieceJustificativesController::class, 'rejeter']);

Route::get('pieces-justificatives/{id}/download', [PieceJustificativesController::class, 'download']);
Route::post('pieces-justificatives/{id}/notifier', [PieceJustificativesController::class, 'notifier']);


/*
|--------------------------------------------------------------------------
| Accusés de réception
|--------------------------------------------------------------------------
*/

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET accuses-reception/{id})
Route::get('accuses-reception/modeles', [AccuseReceptionController::class, 'modeles']);
Route::get('accuses-reception/politique-archivage', [AccuseReceptionController::class, 'politiqueArchivage']);

Route::apiResource('accuses-reception', AccuseReceptionController::class);

Route::get('accuses-reception/{id}/generer', [AccuseReceptionController::class, 'generer']);
Route::post('accuses-reception/{id}/signer', [AccuseReceptionController::class, 'signer']);

Route::get('accuses-reception/{id}/export', [AccuseReceptionController::class, 'export']);
Route::post('accuses-reception/{id}/archiver', [AccuseReceptionController::class, 'archiver']);

Route::get('accuses-reception/{id}/consulter', [AccuseReceptionController::class, 'consulter']);


/*
|--------------------------------------------------------------------------
| Frais de déplacement
|--------------------------------------------------------------------------
*/

// Route à segment fixe déclarée AVANT apiResource() pour ne pas être
// interceptée par la route "show" (GET frais-deplacement/{id}) — liste des
// bénéficiaires d'une convocation dont le dossier de pièces justificatives
// est complet (seuls ceux-là peuvent recevoir une fiche de déplacement).
Route::get('frais-deplacement/beneficiaires-eligibles', [FraisDeplacementController::class, 'beneficiairesEligibles']);

Route::apiResource('frais-deplacement', FraisDeplacementController::class);

Route::post('frais-deplacement/{id}/calculer', [FraisDeplacementController::class, 'calculer']);

Route::get('frais-deplacement/{id}/justificatifs', [FraisDeplacementController::class, 'justificatifs']);
Route::post('frais-deplacement/{id}/justificatifs', [FraisDeplacementController::class, 'deposerJustificatif']);
Route::get('frais-deplacement/{id}/justificatifs/{justificatifId}/download', [FraisDeplacementController::class, 'downloadJustificatif']);
Route::delete('frais-deplacement/{id}/justificatifs/{justificatifId}', [FraisDeplacementController::class, 'supprimerJustificatif']);

Route::post('frais-deplacement/{id}/valider', [FraisDeplacementController::class, 'valider']);
Route::post('frais-deplacement/{id}/rejeter', [FraisDeplacementController::class, 'rejeter']);

Route::post('frais-deplacement/{id}/rembourser', [FraisDeplacementController::class, 'rembourser']);
Route::post('frais-deplacement/{id}/relancer', [FraisDeplacementController::class, 'relancer']);
Route::post('frais-deplacement/{id}/cloturer', [FraisDeplacementController::class, 'cloturer']);

// Téléchargement de la fiche en PDF (demande utilisatrice : "je veux
// pouvoir télécharger la fiche si possible").
Route::get('frais-deplacement/{id}/pdf', [FraisDeplacementPdfController::class, 'generer']);
Route::get('frais-deplacement/{id}/download', [FraisDeplacementPdfController::class, 'download']);


/*
|--------------------------------------------------------------------------
| Indemnités de correction
|--------------------------------------------------------------------------
*/

Route::get('indemnite-correction/correcteurs-eligibles', [IndemniteCorrectionController::class, 'correcteursEligibles']);
Route::post('indemnite-correction/calculer-groupe', [IndemniteCorrectionController::class, 'calculerGroupe']);


/*
|--------------------------------------------------------------------------
| Indemnités de surveillance
|--------------------------------------------------------------------------
*/

Route::get('indemnite-surveillance/surveillants-eligibles', [IndemniteSurveillanceController::class, 'surveillantsEligibles']);
Route::post('indemnite-surveillance/calculer-groupe', [IndemniteSurveillanceController::class, 'calculerGroupe']);


/*
|--------------------------------------------------------------------------
| États de paie des indemnités
|--------------------------------------------------------------------------
*/

// Fiche PDF generee a la volee depuis le filtre courant (pas besoin d'un
// etat de paie deja enregistre) — doit rester avant apiResource() (segment
// fixe, pas de {id}).
Route::post('etat-paie-indemnites/fiche-pdf', [EtatPaieIndemnitesController::class, 'genererFichePdf']);

// Routes à 2 segments déclarées AVANT apiResource() pour ne pas être
// interceptées par la route "show" (GET etat-paie-indemnites/{id})
Route::get('etat-paie-indemnites/individuels', [EtatPaieIndemnitesController::class, 'individuels']);
Route::get('etat-paie-indemnites/consolides', [EtatPaieIndemnitesController::class, 'consolides']);
Route::get('etat-paie-indemnites/export', [EtatPaieIndemnitesController::class, 'export']);
Route::get('etat-paie-indemnites/historique', [EtatPaieIndemnitesController::class, 'historique']);

Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class);

Route::post('etat-paie-indemnites/{id}/generer', [EtatPaieIndemnitesController::class, 'generer']);
Route::get('etat-paie-indemnites/{id}/preview', [EtatPaieIndemnitesController::class, 'preview']);

Route::post('etat-paie-indemnites/{id}/valider', [EtatPaieIndemnitesController::class, 'valider']);
Route::post('etat-paie-indemnites/{id}/archiver', [EtatPaieIndemnitesController::class, 'archiver']);
Route::post('etat-paie-indemnites/{id}/transmettre', [EtatPaieIndemnitesController::class, 'transmettre']);


/*
|--------------------------------------------------------------------------
| Bourses et aides étudiantes
|--------------------------------------------------------------------------
*/


Route::apiResource('bourses', BoursesController::class);

Route::post('bourses/{id}/valider', [BoursesController::class, 'valider']);
Route::post('bourses/{id}/rejeter', [BoursesController::class, 'rejeter']);

Route::post('bourses/{id}/calculer-montant', [BoursesController::class, 'calculerMontant']);

Route::get('bourses/{id}/pieces', [BoursesController::class, 'pieces']);
Route::post('bourses/{id}/pieces', [BoursesController::class, 'deposerPiece']);

Route::post('bourses/{id}/archiver', [BoursesController::class, 'archiver']);


Route::apiResource('aides-etudiantes', AidesEtudiantesController::class);

Route::post('aides-etudiantes/{id}/valider', [AidesEtudiantesController::class, 'valider']);
Route::post('aides-etudiantes/{id}/rejeter', [AidesEtudiantesController::class, 'rejeter']);

Route::post('aides-etudiantes/{id}/calculer-montant', [AidesEtudiantesController::class, 'calculerMontant']);

Route::get('aides-etudiantes/{id}/pieces', [AidesEtudiantesController::class, 'pieces']);
Route::post('aides-etudiantes/{id}/pieces', [AidesEtudiantesController::class, 'deposerPiece']);

Route::post('aides-etudiantes/{id}/archiver', [AidesEtudiantesController::class, 'archiver']);







