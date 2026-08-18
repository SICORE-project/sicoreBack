<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BultinsController;
use App\Http\Controllers\ConvocationsController;
use App\Http\Controllers\DetailBultinsController;
use App\Http\Controllers\EtatPaieIndemnitesController;
use App\Http\Controllers\IndemnitesController;
use App\Http\Controllers\PieceJustificativesController;
use App\Http\Controllers\RubriqueBultinsController;
use App\Http\Controllers\TypeIndemnitesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DelegationCreditController;
use App\Http\Controllers\EditionEngagementController;
use App\Http\Controllers\BulletinSalaireController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;


Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

//Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('delegation-credits/{id}/affectation', [DelegationCreditController::class, 'affecter']
    );
    Route::put('delegation-credits/{id}/montant-disponible', [DelegationCreditController::class, 'definirMontantDisponible']);
    Route::put('delegation-credits/{id}/engager', [DelegationCreditController::class, 'engager']);
    Route::get('delegation-credits/{id}/engagements', [DelegationCreditController::class, 'historiqueEngagements']);
    Route::get('engagements', [DelegationCreditController::class, 'tousEngagements']);
    Route::put('delegation-credits/{id}/consommer', [DelegationCreditController::class, 'consommer']
    );
    Route::get('delegation-credits/{id}/solde', [DelegationCreditController::class, 'solde']
    );
    Route::post('delegation-credits/{id}/paiements', [DelegationCreditController::class, 'ajouterPaiement']
);
Route::get('delegation-credits/{id}/etat', [DelegationCreditController::class, 'etatCredits']
);
    Route::apiResource('users', UserController::class);
    Route::apiResource('indemnites', IndemnitesController::class);
    Route::apiResource('type-indemnites', TypeIndemnitesController::class);
    Route::apiResource('convocations', ConvocationsController::class);
    Route::apiResource('pieces-justificatives', PieceJustificativesController::class);
    Route::apiResource('bultins', BultinsController::class);
    Route::apiResource('detail-bultins', DetailBultinsController::class);
    Route::apiResource('rubrique-bultins', RubriqueBultinsController::class);
    Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class);
    Route::apiResource('delegation-credits', DelegationCreditController::class);
    Route::apiResource('structures', StructureController::class);
    Route::apiResource('services', ServiceController::class);

    // --- Bulletin de salaire ---
    Route::get('bulletins-salaire', [BulletinSalaireController::class, 'index']);
    Route::get('bulletins-salaire/statistiques', [BulletinSalaireController::class, 'statistiques']);
    Route::get('bulletins-salaire/agents', [BulletinSalaireController::class, 'rechercherAgents']);
    Route::post('bulletins-salaire/apercu', [BulletinSalaireController::class, 'apercu']);
    Route::post('bulletins-salaire/generer', [BulletinSalaireController::class, 'generer']);
    Route::post('bulletins-salaire/generer-masse', [BulletinSalaireController::class, 'genererEnMasse']);
    Route::get('bulletins-salaire/{id}', [BulletinSalaireController::class, 'show']);
    Route::put('bulletins-salaire/{id}/valider', [BulletinSalaireController::class, 'valider']);
    Route::put('bulletins-salaire/{id}/annuler', [BulletinSalaireController::class, 'annuler']);

    // --- Édition état des engagements ---
    Route::get('edition-engagements', [EditionEngagementController::class, 'index']);
    Route::get('edition-engagements/filtres', [EditionEngagementController::class, 'filtres']);
    Route::get('edition-engagements/details', [EditionEngagementController::class, 'details']);
    Route::get('edition-engagements/iefs/{iaId}', [EditionEngagementController::class, 'iefsByIa']);
    Route::get('edition-engagements/etablissements/{iefId}', [EditionEngagementController::class, 'etablissementsByIef']);
//});
