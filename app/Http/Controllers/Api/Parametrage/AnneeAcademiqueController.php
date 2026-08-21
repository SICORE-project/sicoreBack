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
    public function __construct(
        private AnneeAcademiqueService $anneeAcademiqueService
    ) {
    }

    /**
     * Liste des années académiques
     */
    // public function index()
    // {
    //     $annees = $this->anneeAcademiqueService->getAll();

    //     return response()->json([
    //         'message' => 'Liste des années académiques récupérée avec succès.',
    //         'data' => $annees,
    //     ], 200);
    // }
   
public function index(Request $request)
{
    $search = $request->query('search');

    $annees = $this->anneeAcademiqueService->getAll($search);

    return response()->json([
        'message' => 'Liste des années académiques récupérée avec succès.',
        'data' => $annees,
    ], 200);
}

    /**
     * Créer une année académique
     */
    public function store(StoreAnneeAcademiqueRequest $request)
    {
        $annee = $this->anneeAcademiqueService->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Année académique créée avec succès.',
            'data' => $annee,
        ], 201);
    }

    /**
     * Afficher une année académique
     */
    public function show($id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        return response()->json([
            'message' => 'Année académique récupérée avec succès.',
            'data' => $annee,
        ], 200);
    }

    /**
     * Modifier une année académique
     */
    public function update(
        UpdateAnneeAcademiqueRequest $request,
        $id
    ) {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        try {
            $annee = $this->anneeAcademiqueService->update(
                $annee,
                $request->validated()
            );

            return response()->json([
                'message' => 'Année académique modifiée avec succès.',
                'data' => $annee,
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Supprimer une année académique
     */
    public function destroy($id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        try {
            $this->anneeAcademiqueService->delete($annee);

            return response()->json([
                'message' => 'Année académique supprimée avec succès.',
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Activer une année académique
     */
    public function activate($id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        try {
            $annee = $this->anneeAcademiqueService->activate($annee);

            return response()->json([
                'message' => 'Année académique activée avec succès.',
                'data' => $annee,
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Désactiver une année académique
     */
    public function deactivate($id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        $annee = $this->anneeAcademiqueService->deactivate($annee);

        return response()->json([
            'message' => 'Année académique désactivée avec succès.',
            'data' => $annee,
        ], 200);
    }

    /**
     * Clôturer une année académique
     */
    public function close($id)
    {
        $annee = $this->anneeAcademiqueService->findById($id);

        if (!$annee) {
            return response()->json([
                'message' => 'Année académique introuvable.',
            ], 404);
        }

        try {
            $annee = $this->anneeAcademiqueService->close($annee);

            return response()->json([
                'message' => 'Année académique clôturée avec succès.',
                'data' => $annee,
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}