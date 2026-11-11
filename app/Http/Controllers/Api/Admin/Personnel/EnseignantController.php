<?php

namespace App\Http\Controllers\Api\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use App\Http\Resources\Personnel\EnseignantResource;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    public function __construct(
        private EnseignantService $enseignantService
    ) {
    }

    /**
     * Liste des enseignants.
     */
    public function checkIdentity(Request $request)
    {
        $data = $request->validate([
            'field' => ['required', 'in:cni,matricule,indice,email'],
            'value' => ['required', 'string', 'max:100'],
            'id' => ['nullable', 'integer', 'min:1'],
        ]);
        $permission = empty($data['id']) ? 'enseignants.create' : 'enseignants.update';
        abort_unless($request->user()->hasRole('super_admin') || $request->user()->hasPermission($permission)
            || ($request->user()->hasRole('gestionnaire_ia') && empty($data['id'])), 403);
        if (! empty($data['id'])) {
            $teacher = $this->enseignantService->find($data['id']);
            $this->assertInPerimetre($request->user(), $teacher->toArray());
        }
        $messages = [
            'cni' => 'Ce numéro de carte d’identité est déjà utilisé par un autre enseignant.',
            'matricule' => 'Ce matricule est déjà utilisé par un autre enseignant.',
            'indice' => 'Cet indice est déjà attribué à un autre enseignant.',
            'email' => 'Cette adresse e-mail est déjà utilisée par un autre enseignant.',
        ];
        $rule = \Illuminate\Validation\Rule::unique('enseignants', $data['field']);
        if (! empty($data['id'])) $rule->ignore($data['id']);
        $exists = \Illuminate\Support\Facades\Validator::make(
            ['value' => $data['value']], ['value' => [$rule]]
        )->fails();

        if (! $exists && \Illuminate\Support\Facades\Schema::hasTable('teacher_submissions')) {
            $exists = \App\Models\Personnel\TeacherSubmission::query()->where('status', 'pending')
                ->where($data['field'] === 'indice' ? 'payload->indice' : $data['field'], $data['value'])->exists();
        }
        return response()->json(['available' => ! $exists, 'message' => $exists ? $messages[$data['field']] : '']);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'statut_enseignant_id' => ['nullable', 'integer', 'min:1'],
            'prenom' => ['nullable', 'string', 'max:50'],
            'nom' => ['nullable', 'string', 'max:50'],
            'corps_id' => ['nullable', 'integer', 'min:1'],
            'diplome_id' => ['nullable', 'integer', 'min:1'],
            'ia_id' => ['nullable', 'integer', 'min:1'],
            'ief_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (int) $request->input('per_page', 20);

        $perPage = min(max($perPage, 1), 100);

        $enseignants = $this->enseignantService
            ->paginate($perPage, $filters, $request->user());

        return EnseignantResource::collection($enseignants)
            ->additional([
                'success' => true,
                'message' => 'Liste des enseignants récupérée avec succès.',
            ]);
    }

    /**
     * Enregistrer un nouvel enseignant.
     */
    public function store(StoreEnseignantRequest $request)
    {
        $data = $request->validated();
        $this->assertInPerimetre($request->user(), $data);

        $enseignant = $this->enseignantService->create(
            $data,
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Enseignant enregistré avec succès.',
            'data' => new EnseignantResource($enseignant),
        ], 201);
    }

    /**
     * Afficher un enseignant.
     */
    public function show(int $id)
    {
        $enseignant = $this->enseignantService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Dossier enseignant récupéré avec succès.',
            'data' => new EnseignantResource($enseignant),
        ]);
    }

    public function update(UpdateEnseignantRequest $request, int $id)
    {
        $data = $request->validated();
        $this->assertInPerimetre($request->user(), $data);

        $enseignant = $this->enseignantService->update(
            $this->enseignantService->find($id),
            $data,
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Enseignant modifié avec succès.',
            'data' => new EnseignantResource($enseignant),
        ]);
    }

    public function destroy(int $id)
    {
        $this->enseignantService->delete($this->enseignantService->find($id));

        return response()->json([
            'success' => true,
            'message' => 'Enseignant supprimé avec succès.',
        ]);
    }

    /** Un gestionnaire IA/IEF ne peut affecter un enseignant qu'à son propre périmètre. */
    private function assertInPerimetre($user, array $data): void
    {
        foreach ($user?->perimetre() ?? [] as $column => $value) {
            if (array_key_exists($column, $data) && (int) $data[$column] !== (int) $value) {
                abort(403, 'Cet enseignant est hors de votre périmètre.');
            }
        }
    }
}
