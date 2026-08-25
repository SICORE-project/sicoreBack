<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreAnneeAcademiqueRequest;
use App\Http\Requests\Parametrage\UpdateAnneeAcademiqueRequest;
use App\Services\Parametrage\AnneeAcademiqueService;
use DomainException;
use Illuminate\Http\Request;

class AnneeAcademiqueController extends Controller
{
    public function __construct(private readonly AnneeAcademiqueService $anneeAcademiqueService) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Liste des années académiques récupérée avec succès.',
            'data' => $this->anneeAcademiqueService->getAll($data['search'] ?? null),
        ]);
    }

    public function store(StoreAnneeAcademiqueRequest $request)
    {
        $annee = $this->anneeAcademiqueService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Année académique créée avec succès.',
            'data' => $annee,
        ], 201);
    }

    public function show(int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Année académique récupérée avec succès.',
            'data' => $annee,
        ]);
    }

    public function update(UpdateAnneeAcademiqueRequest $request, int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        return $this->domainAction(
            fn () => $this->anneeAcademiqueService->update($annee, $request->validated()),
            'Année académique modifiée avec succès.',
        );
    }

    public function destroy(int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        try {
            $this->anneeAcademiqueService->delete($annee);

            return response()->json([
                'success' => true,
                'message' => 'Année académique supprimée avec succès.',
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }
    }

    public function activate(int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        return $this->domainAction(
            fn () => $this->anneeAcademiqueService->activate($annee),
            'Année académique activée avec succès.',
        );
    }

    public function deactivate(int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        return $this->domainAction(
            fn () => $this->anneeAcademiqueService->deactivate($annee),
            'Année académique désactivée avec succès.',
        );
    }

    public function close(int $id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (! $annee) {
            return $this->notFound();
        }

        return $this->domainAction(
            fn () => $this->anneeAcademiqueService->close($annee),
            'Année académique clôturée avec succès.',
        );
    }

    private function domainAction(callable $action, string $message)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $action(),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function notFound()
    {
        return response()->json([
            'success' => false,
            'message' => 'Année académique introuvable.',
        ], 404);
    }
}
