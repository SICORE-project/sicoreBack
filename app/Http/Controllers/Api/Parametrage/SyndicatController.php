<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreSyndicatRequest;
use App\Http\Requests\Parametrage\UpdateSyndicatRequest;
use App\Services\Parametrage\SyndicatService;
use Illuminate\Support\Facades\Log;

class SyndicatController extends Controller
{
    public function __construct(private SyndicatService $syndicatService)
    {
    }

    public function index()
    {
        // return response()->json($this->syndicatService->getAllSyndicats());
    }

    public function show($id)
    {
        // return response()->json($this->syndicatService->getSyndicatById($id));
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

    public function update(UpdateSyndicatRequest $request, $id)
    {
        $syndicat = $this->syndicatService->updateSyndicat($id, $request->validated());
        return response()->json($syndicat);
    }

    public function destroy($id)
    {
        $this->syndicatService->deleteSyndicat($id);
        return response()->json(null, 204);
    }
}
