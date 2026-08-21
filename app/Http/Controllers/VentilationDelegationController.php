<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentilationDelegationRequest;
use App\Http\Requests\UpdateVentilationDelegationRequest;
use App\Http\Resources\VentilationDelegationResource;
use App\Models\Activite;
use App\Models\Budget;
use App\Models\CentreExecution;
use App\Models\corps_enseignants;
use App\Models\DelegationCredit;
use App\Models\ias;
use App\Models\iefs;
use App\Models\VentilationDelegation;
use Illuminate\Http\Request;

/**
 * Ecran FINPRONET frmDetailDelegation.aspx — "Ventilations" d'une delegation.
 */
class VentilationDelegationController extends Controller
{
    private const RELATIONS = [
        'ia', 'ief', 'corpsEnseignant', 'centreExecution', 'budget', 'activite',
    ];

    /** Lignes de ventilation d'une delegation, filtrables comme dans FINPRONET. */
    public function index(Request $request, string $delegationId)
    {
        $delegation = DelegationCredit::findOrFail($delegationId);

        $query = $delegation->ventilations()->with(self::RELATIONS);

        foreach (['type', 'ia_id', 'ief_id', 'corps_enseignant_id', 'numero_carton', 'numero_autorisation'] as $filtre) {
            if ($request->filled($filtre)) {
                $query->where($filtre, $request->input($filtre));
            }
        }

        $ventilations = $query->orderBy('numero_carton')->get();

        return response()->json([
            'data' => VentilationDelegationResource::collection($ventilations),
            'totaux' => [
                'montant' => (float) $ventilations->sum('montant'),
                'montant_engagement' => (float) $ventilations->sum('montant_engagement'),
                'nombre_lignes' => $ventilations->count(),
            ],
        ]);
    }

    public function store(StoreVentilationDelegationRequest $request, string $delegationId)
    {
        $delegation = DelegationCredit::findOrFail($delegationId);

        $ventilation = $delegation->ventilations()->create($request->validated());

        return response()->json([
            'message' => 'Ventilation ajoutée avec succès.',
            'data' => new VentilationDelegationResource($ventilation->load(self::RELATIONS)),
        ], 201);
    }

    public function show(string $id)
    {
        $ventilation = VentilationDelegation::with(self::RELATIONS)->findOrFail($id);

        return new VentilationDelegationResource($ventilation);
    }

    public function update(UpdateVentilationDelegationRequest $request, string $id)
    {
        $ventilation = VentilationDelegation::findOrFail($id);

        $ventilation->update($request->validated());

        return response()->json([
            'message' => 'Ventilation modifiée avec succès.',
            'data' => new VentilationDelegationResource($ventilation->load(self::RELATIONS)),
        ]);
    }

    public function destroy(string $id)
    {
        VentilationDelegation::findOrFail($id)->delete();

        return response()->json(['message' => 'Ventilation supprimée avec succès.']);
    }

    /** Alimente les listes deroulantes de l'ecran Ventilations. */
    public function nomenclature()
    {
        return response()->json([
            'corps_enseignants'  => corps_enseignants::orderBy('libelle')->get(['id', 'libelle']),
            'ias'                => ias::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'iefs'               => iefs::orderBy('libelle')->get(['id', 'code', 'libelle', 'ia_id']),
            'centres_execution'  => CentreExecution::orderBy('code')->get(['id', 'code', 'libelle']),
            'budgets'            => Budget::orderBy('code')->get(['id', 'code', 'libelle']),
            'activites'          => Activite::orderBy('code')->get(['id', 'code', 'libelle']),
            'types'              => [
                ['value' => VentilationDelegation::TYPE_SALAIRE, 'label' => 'Etat sur salaire'],
                ['value' => VentilationDelegation::TYPE_PRIME_SCOLAIRE, 'label' => 'Etat sur prime scolaire'],
            ],
        ]);
    }
}
