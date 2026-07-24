<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1'])->name('login.submit');
Route::middleware('auth')->group(function () {
Route::view('/dashboard', 'pages.dashboard.index')->name('dashboard');

Route::view('/enseignants', 'pages.enseignants.index')->name('enseignants.index');
Route::view('/enseignants/nouveau', 'pages.enseignants.create')->name('enseignants.create');

Route::view('/parametres', 'pages.parametres.index')->name('parametres.index');
Route::view('/parametres/ief', 'pages.parametres.ief')->name('parametres.ief');

Route::view('/paie/etats-presence', 'pages.paie.etats-presence')->name('paie.etats-presence');
Route::view('/paie/avance-tabaski', 'pages.paie.avance-tabaski')->name('paie.avance-tabaski');
Route::view('/paie/retenue-tabaski', 'pages.paie.retenue-tabaski')->name('paie.retenue-tabaski');
Route::view('/paie/retenues-rappel', 'pages.paie.retenues-rappel')->name('paie.retenues-rappel');
Route::view('/paie/exemptions', 'pages.paie.exemptions')->name('paie.exemptions');
Route::view('/paie/travaux-periodiques', 'pages.paie.travaux-periodiques')->name('paie.travaux-periodiques');
Route::view('/paie/recap-banque', 'pages.paie.recap-banque')->name('paie.recap-banque');
Route::view('/paie/cotisations-sociales', 'pages.paie.cotisations-sociales')->name('paie.cotisations-sociales');
Route::view('/paie/etat-salaires', 'pages.paie.etat-salaires')->name('paie.etat-salaires');
Route::view('/paie/elements-saisie-dashboard', 'pages.paie.elements-saisie-dashboard')->name('paie.elements-saisie-dashboard');
Route::view('/paie/generee-ief', 'pages.paie.generee-ief')->name('paie.generee-ief');
Route::view('/paie/fermeture-periode', 'pages.paie.fermeture-periode')->name('paie.fermeture-periode');
Route::view('/paie/edition-salaires-banque', 'pages.paie.edition-salaires-banque')->name('paie.edition-salaires-banque');
Route::view('/paie/bulletins', 'pages.paie.bulletins')->name('paie.bulletins');
Route::view('/paie/effectifs-corps', 'pages.paie.effectifs-corps')->name('paie.effectifs-corps');
Route::view('/paie/non-generee', 'pages.paie.non-generee')->name('paie.non-generee');
Route::view('/paie/sommes-percues', 'pages.paie.sommes-percues')->name('paie.sommes-percues');

Route::view('/credits/delegation', 'pages.credits.delegation')->name('credits.delegation');
Route::view('/credits/edition-delegations', 'pages.credits.edition-delegations')->name('credits.edition-delegations');
Route::view('/credits/edition-engagements', 'pages.credits.edition-engagements')->name('credits.edition-engagements');

Route::view('/indemnites/convocations', 'pages.indemnites.convocations')->name('indemnites.convocations');
Route::view('/indemnites/services-faits', 'pages.indemnites.services-faits')->name('indemnites.services-faits');
Route::view('/indemnites/pieces-justificatives', 'pages.indemnites.pieces-justificatives')->name('indemnites.pieces-justificatives');
Route::view('/indemnites/accuses-reception', 'pages.indemnites.accuses-reception')->name('indemnites.accuses-reception');
Route::view('/indemnites/calcul', 'pages.indemnites.calcul')->name('indemnites.calcul');
Route::view('/indemnites/frais-deplacement', 'pages.indemnites.frais-deplacement')->name('indemnites.frais-deplacement');
Route::view('/indemnites/etats-paie', 'pages.indemnites.etats-paie')->name('indemnites.etats-paie');

Route::view('/bourses/enregistrer-demande', 'pages.bourses.enregistrer-demande')->name('bourses.enregistrer-demande');
Route::view('/bourses/valider-dossier', 'pages.bourses.valider-dossier')->name('bourses.valider-dossier');
Route::view('/bourses/attribuer-aide', 'pages.bourses.attribuer-aide')->name('bourses.attribuer-aide');

Route::middleware('role:Administrateur,Super administrateur')->group(function () {
Route::view('/utilisateurs', 'pages.administration.utilisateurs')->name('utilisateurs.index');
Route::view('/utilisateurs/profils-roles', 'pages.administration.profils-roles')->name('utilisateurs.profils-roles');
Route::view('/utilisateurs/permissions', 'pages.administration.permissions')->name('utilisateurs.permissions');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::redirect('/index.html', '/', 301);
Route::redirect('/dashboard.html', '/dashboard', 301);
Route::redirect('/enseignant-dashboard.html', '/enseignants', 301);
Route::redirect('/enseignant-form.html', '/enseignants/nouveau', 301);
Route::redirect('/parametres.html', '/parametres', 301);
Route::redirect('/ief.html', '/parametres/ief', 301);

Route::redirect('/paie-etats-presence.html', '/paie/etats-presence', 301);
Route::redirect('/paie-avance-tabaski.html', '/paie/avance-tabaski', 301);
Route::redirect('/paie-retenue-tabaski.html', '/paie/retenue-tabaski', 301);
Route::redirect('/paie-retenues-rappel.html', '/paie/retenues-rappel', 301);
Route::redirect('/paie-exemptions.html', '/paie/exemptions', 301);
Route::redirect('/paie-travaux-periodiques.html', '/paie/travaux-periodiques', 301);
Route::redirect('/paie-recap-banque.html', '/paie/recap-banque', 301);
Route::redirect('/paie-cotisations-sociales.html', '/paie/cotisations-sociales', 301);
Route::redirect('/paie-etat-salaires.html', '/paie/etat-salaires', 301);
Route::redirect('/paie-elements-saisie-dashboard.html', '/paie/elements-saisie-dashboard', 301);
Route::redirect('/paie-generee-ief.html', '/paie/generee-ief', 301);
Route::redirect('/paie-fermeture-periode.html', '/paie/fermeture-periode', 301);
Route::redirect('/paie-edition-salaires-banque.html', '/paie/edition-salaires-banque', 301);
Route::redirect('/paie-bulletins.html', '/paie/bulletins', 301);
Route::redirect('/paie-effectifs-corps.html', '/paie/effectifs-corps', 301);
Route::redirect('/paie-non-generee.html', '/paie/non-generee', 301);
Route::redirect('/paie-sommes-percues.html', '/paie/sommes-percues', 301);

Route::redirect('/credit-delegation.html', '/credits/delegation', 301);
Route::redirect('/credit-edition-delegations.html', '/credits/edition-delegations', 301);
Route::redirect('/credit-edition-engagements.html', '/credits/edition-engagements', 301);

Route::redirect('/indemnites-convocations.html', '/indemnites/convocations', 301);
Route::redirect('/indemnites-services-faits.html', '/indemnites/services-faits', 301);
Route::redirect('/indemnites-pieces-justificatives.html', '/indemnites/pieces-justificatives', 301);
Route::redirect('/indemnites-accuses-reception.html', '/indemnites/accuses-reception', 301);
Route::redirect('/indemnites-calcul.html', '/indemnites/calcul', 301);
Route::redirect('/indemnites-frais-deplacement.html', '/indemnites/frais-deplacement', 301);
Route::redirect('/indemnites-etats-paie.html', '/indemnites/etats-paie', 301);

Route::redirect('/bourses-enregistrer-demande.html', '/bourses/enregistrer-demande', 301);
Route::redirect('/bourses-valider-dossier.html', '/bourses/valider-dossier', 301);
Route::redirect('/bourses-attribuer-aide.html', '/bourses/attribuer-aide', 301);

Route::redirect('/utilisateurs.html', '/utilisateurs', 301);
Route::redirect('/profils-roles.html', '/utilisateurs/profils-roles', 301);
Route::redirect('/permissions.html', '/utilisateurs/permissions', 301);
});
