<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\IndexInstitutFinancierRequest;
use App\Http\Requests\Parametrage\StoreInstitutFinancierRequest;
use App\Http\Requests\Parametrage\UpdateInstitutFinancierRequest;
use App\Http\Requests\Parametrage\UpdateStatutInstitutFinancierRequest;
use App\Http\Resources\Parametrage\InstitutFinancierResource;
use App\Models\Parametrage\Guichet;
use App\Models\Parametrage\InstitutFinancier;
use App\Models\Personnel\CompteBancaireEnseignant;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstitutFinancierController extends Controller
{
    public function index(IndexInstitutFinancierRequest $request)
    {
        $validated = $request->validated();
        $query = InstitutFinancier::query()->with('guichets');

        if (isset($validated['search']) && $validated['search'] !== '') {
            $term = $validated['search'];
            $query->where(fn ($query) => $query
                ->where('libelle', 'like', "%{$term}%")
                ->orWhere('sigle', 'like', "%{$term}%")
                ->orWhere('code_banque', 'like', "%{$term}%"));
        }

        // if (! empty($validated['type_institution'])) {
        //     $query->where('type_institution', $validated['type_institution']);
        // }

        // if (array_key_exists('est_actif', $validated)) {
        //     $query->where('est_actif', $request->boolean('est_actif'));
        // }

        $institutions = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return InstitutFinancierResource::collection($institutions)->additional([
            'success' => true,
            'message' => 'Liste des banques.',
            'stats' => [
                'total' => InstitutFinancier::count(),
                'guichets' => Guichet::count(),
                'comptes' => CompteBancaireEnseignant::count(),
                'enseignants' => CompteBancaireEnseignant::distinct()->count('enseignant_id'),
            ],
        ]);
    }

    // public function store(StoreInstitutFinancierRequest $request)
    // {
    //     $institution = InstitutFinancier::create($request->validated());

    //     return (new InstitutFinancierResource($institution))
    //         ->additional([
    //             'success' => true,
    //             'message' => 'Banque créée avec succès.',
    //         ])
    //         ->response()
    //         ->setStatusCode(201);
    // }
    public function store(StoreInstitutFinancierRequest $request)
    {
        $institution = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $guichets = $data['guichets'] ?? [];
            unset($data['guichets']);
            $institution = InstitutFinancier::create($data);
            $institution->guichets()->createMany($guichets);

            return $institution;
        });

        return (new InstitutFinancierResource($institution->load('guichets')))
            ->additional([
                'success' => true,
                'message' => 'Banque créée avec succès.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    // public function update(UpdateInstitutFinancierRequest $request, InstitutFinancier $institution)
    // {
    //     $institution->update($request->validated());

    //     return (new InstitutFinancierResource($institution->refresh()))
    //         ->additional([
    //             'success' => true,
    //             'message' => 'Banque modifiée avec succès.',
    //         ]);
    // }
    public function update(UpdateInstitutFinancierRequest $request, InstitutFinancier $institution)
    {
        DB::transaction(function () use ($request, $institution) {
            $data = $request->validated();
            $guichets = $data['guichets'] ?? [];
            unset($data['guichets']);
            $institution->update($data);

            if ($request->has('guichets')) {
                $institution->guichets()->delete();
                $institution->guichets()->createMany($guichets);
            }
        });

        return (new InstitutFinancierResource($institution->refresh()->load('guichets')))
            ->additional([
                'success' => true,
                'message' => 'Banque modifiée avec succès.',
            ]);
    }

    // public function updateStatut(UpdateStatutInstitutFinancierRequest $request, InstitutFinancier $institution)
    // {
    //     $institution->update([
    //         'est_actif' => $request->validated('est_actif'),
    //     ]);

    //     $message = $institution->est_actif
    //         ? 'Banque activée avec succès.'
    //         : 'Banque désactivée avec succès.';

    //     return (new InstitutFinancierResource($institution->refresh()))
    //         ->additional([
    //             'success' => true,
    //             'message' => $message,
    //         ]);
    // }

    public function destroy(InstitutFinancier $institution): JsonResponse
    {
        if ($institution->comptesBancairesEnseignants()->exists() || $institution->lieuxPaiement()->exists()) {
            return response()->json([
                'message' => 'Cette banque est utilisée et ne peut pas être supprimée.',
            ], 409);
        }

        try {
            $institution->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cette banque est utilisée et ne peut pas être supprimée.',
            ], 409);
        }

        return response()->json([
            'message' => 'Banque supprimée avec succès.',
        ]);
    }
}
