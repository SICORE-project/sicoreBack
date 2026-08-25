<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\IndexInstitutFinancierRequest;
use App\Http\Requests\Parametrage\StoreInstitutFinancierRequest;
use App\Http\Requests\Parametrage\UpdateInstitutFinancierRequest;
use App\Http\Requests\Parametrage\UpdateStatutInstitutFinancierRequest;
use App\Http\Resources\Parametrage\InstitutFinancierResource;
use App\Models\Parametrage\InstitutFinancier;

class InstitutFinancierController extends Controller
{
    public function index(IndexInstitutFinancierRequest $request)
    {
        $validated = $request->validated();
        $query = InstitutFinancier::query();

        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$term}%")
                ->orWhere('libelle', 'like', "%{$term}%")
                ->orWhere('sigle', 'like', "%{$term}%"));
        }

        if (! empty($validated['type_institution'])) {
            $query->where('type_institution', $validated['type_institution']);
        }

        if (array_key_exists('est_actif', $validated)) {
            $query->where('est_actif', $request->boolean('est_actif'));
        }

        $institutions = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return InstitutFinancierResource::collection($institutions)->additional([
            'success' => true,
            'message' => 'Liste des institutions financières.',
        ]);
    }

    public function store(StoreInstitutFinancierRequest $request)
    {
        $institution = InstitutFinancier::create($request->validated());

        return (new InstitutFinancierResource($institution))
            ->additional([
                'success' => true,
                'message' => 'Institution financière créée avec succès.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateInstitutFinancierRequest $request, InstitutFinancier $institution)
    {
        $institution->update($request->validated());

        return (new InstitutFinancierResource($institution->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Institution financière modifiée avec succès.',
            ]);
    }

    public function updateStatut(UpdateStatutInstitutFinancierRequest $request, InstitutFinancier $institution)
    {
        $institution->update([
            'est_actif' => $request->validated('est_actif'),
        ]);

        $message = $institution->est_actif
            ? 'Institution financière activée avec succès.'
            : 'Institution financière désactivée avec succès.';

        return (new InstitutFinancierResource($institution->refresh()))
            ->additional([
                'success' => true,
                'message' => $message,
            ]);
    }
}
