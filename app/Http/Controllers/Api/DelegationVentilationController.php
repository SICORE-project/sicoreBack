<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DelegationCredit;
use App\Models\VentilationCredit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\DelegationEngagementService;

class DelegationVentilationController extends Controller
{
    /**
     * Liste des ventilations d'une délégation.
     */
   
public function index(
    DelegationCredit $delegation,
    DelegationEngagementService $engagementService
): JsonResponse {
    $ventilations = $delegation->ventilations()
        ->orderByDesc('id')
        ->get();

    foreach ($ventilations as $ventilation) {
        $ventilation->setAttribute(
            'engagement',
            $engagementService->calculate(
                $delegation,
                $ventilation
            )
        );
    }

    return response()->json([
        'data' => $ventilations,
        'delegation' => $delegation->load('anneeAcademique'),
        'stats' => [
            'total' => $ventilations->count(),
            'montant_total' => $ventilations->sum('montant'),
        ],
    ]);
}


    /**
     * Options du formulaire.
     */
    public function options(DelegationCredit $delegation): JsonResponse
    {
        $centres = config('delegation-ventilations.centres_execution', []);
$budgets = config('delegation-ventilations.budgets', []);
$activites = config('delegation-ventilations.activites', []);
        return response()->json([
            'delegation' => $delegation->load('anneeAcademique'),

            'corps_enseignants' => DB::table('corps_enseignant')
                ->orderBy('libelle')
                ->get(['id', 'code', 'libelle']),

            'ias' => DB::table('ias')
                ->where('est_actif', 1)
                ->whereNull('deleted_at')
                ->orderBy('libelle')
                ->get(['id', 'code', 'libelle']),

            'iefs' => DB::table('iefs')
                ->where('est_actif', 1)
                ->whereNull('deleted_at')
                ->orderBy('libelle')
                ->get(['id', 'ia_id', 'code', 'libelle']),

            'types_ventilation' => [
                ['value' => 'salaire', 'label' => 'Salaire'],
                ['value' => 'prime_scolaire', 'label' => 'Prime scolaire'],
            ],

            'centres_execution' => collect($centres)
    ->map(fn ($item, $code) => [
        'code' => (string) $code,
        'libelle' => $item['libelle'],
        'ia_code' => $item['ia_code'],
    ])->values()->all(),

'budgets' => collect($budgets)
    ->map(fn ($item, $code) => [
        'code' => (string) $code,
        'libelle' => $item['libelle'],
    ])->values()->all(),

'activites' => collect($activites)
    ->map(fn ($item, $code) => [
        'code' => (string) $code,
        'libelle' => $item['libelle'],
    ])->values()->all(),
        ]);
    }

    /**
     * Afficher une ventilation.
     */
    public function show(
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): JsonResponse {
        $this->ensureBelongsToDelegation($delegation, $ventilation);

        return response()->json([
            'data' => $ventilation,
        ]);
    }

    /**
     * Ajouter une ventilation.
     */
    public function store(
        Request $request,
        DelegationCredit $delegation
    ): JsonResponse {
        $validated = $this->validateVentilation($request);
// Récupération automatique du numéro de carton
$validated['numero_carton'] = $validated['numero_autorisation'];
        $ventilation = DB::transaction(function () use (
            $delegation,
            $validated
        ) {
            $delegation = DelegationCredit::query()
                ->lockForUpdate()
                ->findOrFail($delegation->id);

            $ventilation = $delegation->ventilations()
                ->create($validated);

            $this->updateMontantTotal($delegation);

            return $ventilation;
        });

        return response()->json([
            'message' => 'Ventilation créée avec succès.',
            'data' => $ventilation,
        ], 201);
    }

    /**
     * Modifier une ventilation.
     */
    public function update(
        Request $request,
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): JsonResponse {
        $this->ensureBelongsToDelegation($delegation, $ventilation);

        $validated = $this->validateVentilation($request);
        // Synchroniser le numéro de carton avec le numéro d'autorisation
$validated['numero_carton'] = $validated['numero_autorisation'];

        $ventilation = DB::transaction(function () use (
            $delegation,
            $ventilation,
            $validated
        ) {
            $delegation = DelegationCredit::query()
                ->lockForUpdate()
                ->findOrFail($delegation->id);

            $ventilation = $delegation->ventilations()
                ->whereKey($ventilation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $ventilation->update($validated);

            $this->updateMontantTotal($delegation);

            return $ventilation->refresh();
        });

        return response()->json([
            'message' => 'Ventilation modifiée avec succès.',
            'data' => $ventilation,
        ]);
    }



    public function recuperer(
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): JsonResponse {
    
        $this->ensureBelongsToDelegation(
            $delegation,
            $ventilation
        );
    
        $ventilation = DB::transaction(function () use (
            $delegation,
            $ventilation
        ) {
            $ventilation = $delegation->ventilations()
                ->whereKey($ventilation->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            if (blank($ventilation->numero_autorisation)) {
                throw ValidationException::withMessages([
                    'numero_autorisation' =>
                        "Le numéro d'autorisation est obligatoire pour la récupération.",
                ]);
            }
    
            $ventilation->numero_carton =
                $ventilation->numero_autorisation;
    
            $ventilation->save();
    
            return $ventilation->refresh();
        });
    
        return response()->json([
            'message' => 'Ventilation récupérée avec succès.',
            'data' => $ventilation,
        ]);
    }
    /**
     * Supprimer une ventilation.
     */
    public function destroy(
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): JsonResponse {
        $this->ensureBelongsToDelegation($delegation, $ventilation);

        DB::transaction(function () use ($delegation, $ventilation) {
            $delegation = DelegationCredit::query()
                ->lockForUpdate()
                ->findOrFail($delegation->id);

            $ventilation = $delegation->ventilations()
                ->whereKey($ventilation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $ventilation->delete();

            $this->updateMontantTotal($delegation);
        });

        return response()->json([
            'message' => 'Ventilation supprimée avec succès.',
        ]);
    }

    /**
     * Validation des champs.
     */
    private function validateVentilation(Request $request): array
    {
        $validated = $request->validate([
            'corps_enseignant_id' => [
                'required',
                'integer',
                Rule::exists('corps_enseignant', 'id'),
            ],

            'ia_id' => [
                'required',
                'integer',
                Rule::exists('ias', 'id')
                    ->where('est_actif', 1)
                    ->whereNull('deleted_at'),
            ],

          'ief_id' => ['nullable', 'integer', 'exists:iefs,id'],

            'centre_execution' => [
                'required', 'string', 'max:255',
            ],

            'budget' => [
                'required', 'string', 'max:255',
            ],

            'activite' => [
                'required', 'string', 'max:255',
            ],

            'imputation_budgetaire' => [
                'required', 'string', 'max:255',
            ],

            'numero_autorisation' => [
                'required', 'string', 'max:255',
            ],

         

            'type_ventilation' => [
                'required',
                Rule::in(['salaire', 'prime_scolaire']),
            ],

            'montant' => [
                'required', 'numeric', 'gt:0',
                'decimal:0,2',
                'max:9999999999999.99',
            ],
        ]);

      
        // L'IEF est facultative.
        // La vérification s'effectue uniquement si une IEF est sélectionnée.

        $iefId = $validated['ief_id'] ?? null;

        if ($iefId !== null && $iefId !== '') {

            $iefBelongsToIa = DB::table('iefs')
                ->where('id', $iefId)
                ->where('ia_id', $validated['ia_id'])
                ->exists();

            if (!$iefBelongsToIa) {
                throw ValidationException::withMessages([
                    'ief_id' => "L'IEF sélectionnée n'appartient pas à l'IA choisie.",
                ]);
            }
        }

        // Enregistrement au niveau de l'IA uniquement
        // lorsqu'aucune IEF n'est renseignée.
        $validated['ief_id'] = $iefId;




        return $validated;


       
    }

    /**
     * Empêcher l'accès à une ventilation
     * appartenant à une autre délégation.
     */
    private function ensureBelongsToDelegation(
        DelegationCredit $delegation,
        VentilationCredit $ventilation
    ): void {
        abort_unless(
            (int) $ventilation->delegation_credit_id === (int) $delegation->id,
            404
        );
    }

    /**
     * Recalculer le montant total de la délégation.
     */
    private function updateMontantTotal(
        DelegationCredit $delegation
    ): void {
        $total = $delegation->ventilations()->sum('montant');

        $delegation->update([
            'montant_total' => $total,
        ]);
    }
}