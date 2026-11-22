<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DelegationCredit;
use App\Models\Paie\AnneeAcademique;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\LieuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DelegationCreditController extends Controller
{
    /**
     * Liste des délégations de crédits.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'annee_academique_id' => [
                'nullable',
                'integer',
                'exists:annee_academiques,id',
            ],
            'periode_paie' => [
                'nullable',
                'string',
                'max:100',
            ],
            'reference_lettre' => [
                'nullable',
                'string',
                'max:191',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'in:10,25,50,100',
            ],
        ]);

        $query = DelegationCredit::query()
            ->with([
                'anneeAcademique',
                'ventilations',
            ])
            ->latest('date_delegation')
            ->latest('id');

        if (!empty($filters['annee_academique_id'])) {
            $query->where(
                'annee_academique_id',
                $filters['annee_academique_id']
            );
        }

        if (!empty($filters['periode_paie'])) {
            $query->where(
                'periode_paie',
                'like',
                '%' . $filters['periode_paie'] . '%'
            );
        }

        if (!empty($filters['reference_lettre'])) {
            $query->where(
                'reference_lettre',
                'like',
                '%' . $filters['reference_lettre'] . '%'
            );
        }

        $delegations = $query->paginate(
            $filters['per_page'] ?? 10
        );

        $stats = [
            'total' => DelegationCredit::count(),

            'montant_total' => DelegationCredit::sum(
                'montant_total'
            ),

            'ventilations' => DB::table(
                'ventilations_credits'
            )->count(),
        ];

        return response()->json([
            'data' => $delegations->items(),

            'meta' => [
                'current_page' => $delegations->currentPage(),
                'last_page' => $delegations->lastPage(),
                'per_page' => $delegations->perPage(),
                'total' => $delegations->total(),
            ],

            'stats' => $stats,
        ]);
    }

    /**
     * Options nécessaires aux formulaires.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'annees_academiques' => AnneeAcademique::query()
                ->orderByDesc('date_debut')
                ->get([
                    'id',
                    'libelle',
                    'est_active',
                ]),

            'ias' => Ia::query()
                ->where('est_actif', true)
                ->orderBy('libelle')
                ->get([
                    'id',
                    'code',
                    'libelle',
                ]),

            'iefs' => Ief::query()
                ->where('est_actif', true)
                ->orderBy('libelle')
                ->get([
                    'id',
                    'code',
                    'libelle',
                    'ia_id',
                ]),

            'lieux_services' => LieuService::query()
                ->where('est_actif', true)
                ->orderBy('libelle')
                ->get([
                    'id',
                    'code',
                    'libelle',
                    'ia_id',
                    'ief_id',
                ]),
        ]);
    }

    /**
     * Afficher une délégation.
     */
    public function show(
        DelegationCredit $delegation
    ): JsonResponse {
        return response()->json([
            'data' => $delegation->load([
                'anneeAcademique',
                'ventilations',
            ]),
        ]);
    }

    /**
     * Créer une délégation sans ventilation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateDelegation($request);

        $delegation = DelegationCredit::create([
            'annee_academique_id' =>
                $validated['annee_academique_id'],

            'reference_lettre' =>
                $validated['reference_lettre'],

            'objet' =>
                $validated['objet'],

            'periode_paie' =>
                $validated['periode_paie'],

            'date_delegation' =>
                $validated['date_delegation'],

            'montant_total' => 0,
        ]);

        return response()->json([
            'message' =>
                'Délégation de crédits créée avec succès.',

            'data' => $delegation->load([
                'anneeAcademique',
                'ventilations',
            ]),
        ], 201);
    }

    /**
     * Modifier uniquement les informations
     * de la délégation.
     *
     * Les ventilations existantes sont conservées.
     */
    public function update(
        Request $request,
        DelegationCredit $delegation
    ): JsonResponse {
        $validated = $this->validateDelegation($request);

        $delegation = DB::transaction(
            function () use ($delegation, $validated) {

                $delegation = DelegationCredit::query()
                    ->lockForUpdate()
                    ->findOrFail($delegation->id);

                $delegation->update([
                    'annee_academique_id' =>
                        $validated['annee_academique_id'],

                    'reference_lettre' =>
                        $validated['reference_lettre'],

                    'objet' =>
                        $validated['objet'],

                    'periode_paie' =>
                        $validated['periode_paie'],

                    'date_delegation' =>
                        $validated['date_delegation'],
                ]);

                return $delegation->load([
                    'anneeAcademique',
                    'ventilations',
                ]);
            }
        );

        return response()->json([
            'message' =>
                'Délégation de crédits modifiée avec succès.',

            'data' => $delegation,
        ]);
    }

    /**
     * Supprimer une délégation.
     *
     * Une délégation possédant des ventilations
     * ne peut pas être supprimée directement.
     */
    public function destroy(
        DelegationCredit $delegation
    ): JsonResponse {
        return DB::transaction(function () use ($delegation) {

            $delegation = DelegationCredit::query()
                ->lockForUpdate()
                ->findOrFail($delegation->id);

            if ($delegation->ventilations()->exists()) {
                return response()->json([
                    'message' =>
                        'Cette délégation possède des ventilations. '
                        . 'Veuillez d’abord supprimer ses ventilations.',
                ], 422);
            }

            $delegation->delete();

            return response()->json([
                'message' =>
                    'Délégation de crédits supprimée avec succès.',
            ]);
        });
    }

    /**
     * Validation du formulaire principal.
     *
     * La période de paie est une saisie libre.
     * Aucune ventilation n'est obligatoire ici.
     */
    private function validateDelegation(
        Request $request
    ): array {
        return $request->validate([
            'annee_academique_id' => [
                'required',
                'integer',
                'exists:annee_academiques,id',
            ],

            'reference_lettre' => [
                'required',
                'string',
                'max:191',
            ],

            'objet' => [
                'required',
                'string',
                'max:5000',
            ],

            'periode_paie' => [
                'required',
                'string',
                'max:100',
            ],

            'date_delegation' => [
                'required',
                'date',
            ],
        ]);
    }
}