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

        // Champs autorisés pour le tri
        $allowedSorts = [
            'code',
            'libelle',
            'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'libelle';
        }

        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc');

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id');

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
        return Ia::create($data)->load('region');
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

}
