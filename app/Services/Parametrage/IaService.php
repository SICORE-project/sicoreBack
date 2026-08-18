<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Ia;

class IaService
{
    /**
     * Récupérer les IA avec recherche, filtres, tri et pagination.
     */
    public function getAll(array $filters = [])
    {
        $query = Ia::with('region');

        // Recherche par code ou libellé
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('libelle', 'like', '%' . $search . '%');
            });
        }

        // Filtre par région
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }

        // Filtre actif / inactif
        if (isset($filters['est_actif']) && $filters['est_actif'] !== '') {
            $query->where(
                'est_actif',
                filter_var($filters['est_actif'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Champs autorisés pour le tri
        $allowedSorts = [
            'code',
            'libelle',
        ];

        $sortBy = $filters['sort_by'] ?? 'libelle';

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'libelle';
        }

        $sortDirection = strtolower($filters['sort_direction'] ?? 'asc');

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = (int) ($filters['per_page'] ?? 15);

        if ($perPage < 1) {
            $perPage = 15;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer une IA par son ID.
     */
    public function findById(int $id): Ia
    {
        return Ia::with('region')->findOrFail($id);
    }

    /**
     * Créer une IA.
     */
    public function create(array $data): Ia
    {
        $data['est_actif'] = $data['est_actif'] ?? true;

        return Ia::create($data);
    }

    /**
     * Modifier une IA.
     */
    public function update(int $id, array $data): Ia
    {
        $ia = $this->findById($id);

        $ia->update($data);

        return $ia->fresh(['region']);
    }

    /**
     * Supprimer logiquement une IA.
     */
    public function delete(int $id): void
    {
        $ia = $this->findById($id);

        $ia->delete();
    }

    /**
     * Activer ou désactiver une IA.
     */
    public function changeStatus(int $id, bool $newStatus): Ia
{
    $ia = $this->findById($id);

    if ($ia->est_actif === $newStatus) {
        throw new \DomainException(
            $newStatus
                ? 'Cette IA est déjà active.'
                : 'Cette IA est déjà inactive.'
        );
    }

    $ia->est_actif = $newStatus;
    $ia->save();

    return $ia->fresh(['region']);
}
}