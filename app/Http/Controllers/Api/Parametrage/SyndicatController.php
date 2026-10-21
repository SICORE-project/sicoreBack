<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreSyndicatRequest;
use App\Http\Requests\Parametrage\UpdateSyndicatRequest;
use App\Services\Parametrage\SyndicatService;
use Illuminate\Support\Facades\Log;

class SyndicatController extends Controller
{
    public function __construct(private SyndicatService $syndicatService) {}

    public function index()
    {
        return response()->json($this->syndicatService->getAllSyndicats());
    }

    public function show(int $id)
    {
        return response()->json($this->syndicatService->getSyndicatById($id));
    }

    public function store(StoreSyndicatRequest $request)
    {
        $syndicat = $this->syndicatService
            ->createSyndicat($request->validated())
            ->refresh();

        Log::info('Création d’un syndicat', [
            'syndicat_id' => $syndicat->id,
            'code' => $syndicat->code,
            'utilisateur_id' => $request->user()?->id,
            'adresse_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Syndicat créé avec succès.',
            'data' => $syndicat->only([
                'id',
                'code',
                'libelle',
                'montant_check_off',
                'montant_oeuvre_sociale',
                'est_actif',
                'created_at',
                'updated_at',
            ]),
        ], 201);
    }

    public function update(UpdateSyndicatRequest $request, int $id)
    {
        $donneesValidees = $request->validated();
        $syndicat = $this->syndicatService->updateSyndicat(
            $id,
            $donneesValidees,
        );

        $champsModifies = array_values(array_intersect(
            array_keys($donneesValidees),
            array_keys($syndicat->getChanges()),
        ));

        $anciennesValeurs = array_intersect_key(
            $syndicat->getPrevious(),
            array_flip($champsModifies),
        );

        $nouvellesValeurs = $syndicat->only($champsModifies);

        Log::info('Modification d’un syndicat', [
            'syndicat_id' => $syndicat->id,
            'champs_modifies' => $champsModifies,
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'utilisateur_id' => $request->user()?->id,
            'adresse_ip' => $request->ip(),
        ]);

        $syndicat->refresh();

        return response()->json([
            'message' => 'Syndicat modifié avec succès.',
            'data' => $syndicat->only([
                'id',
                'code',
                'libelle',
                'montant_check_off',
                'montant_oeuvre_sociale',
                'est_actif',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    public function activate(int $id)
    {
        return $this->changeActiveStatus($id, true);
    }

    public function deactivate(int $id)
    {
        return $this->changeActiveStatus($id, false);
    }

    private function changeActiveStatus(int $id, bool $estActif)
    {
        $syndicat = $this->syndicatService->setActiveStatus($id, $estActif);
        $ancienStatut = array_key_exists('est_actif', $syndicat->getPrevious())
            ? (bool) $syndicat->getPrevious()['est_actif']
            : $syndicat->est_actif;

        Log::info($estActif ? 'Activation d’un syndicat' : 'Désactivation d’un syndicat', [
            'syndicat_id' => $syndicat->id,
            'ancienne_valeur' => $ancienStatut,
            'nouvelle_valeur' => $syndicat->est_actif,
            'utilisateur_id' => request()->user()?->id,
            'adresse_ip' => request()->ip(),
        ]);

        return response()->json([
            'message' => $estActif
                ? 'Syndicat activé avec succès.'
                : 'Syndicat désactivé avec succès.',
            'data' => $syndicat->only([
                'id',
                'code',
                'libelle',
                'montant_check_off',
                'montant_oeuvre_sociale',
                'est_actif',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    public function destroy(int $id)
    {
        $this->syndicatService->deleteSyndicat($id);

        return response()->json([
            'message' => 'Syndicat supprimé avec succès.',
        ]);
    }
}
