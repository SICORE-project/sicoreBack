<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;

class IefService
{
    /**
     * Récupérer les IEF avec recherche, filtres, tri et pagination.
     */
    public function getAll(array $filters = [])
    {
        $query = Ief::with('ia');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('libelle', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['ia_id'])) {
            $query->where('ia_id', $filters['ia_id']);
        }

        if (isset($filters['est_actif']) && $filters['est_actif'] !== '') {
            $query->where(
                'est_actif',
                filter_var(
                    $filters['est_actif'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $allowedSorts = [
            'code',
            'libelle',
        ];

        $sortBy = $filters['sort_by'] ?? 'libelle';

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'libelle';
        }

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'asc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection);

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
     * Récupérer une IEF.
     */
    public function findById(int $id): Ief
    {
        return Ief::with('ia')->findOrFail($id);
    }

    /**
     * Créer une IEF.
     */
    public function create(array $data): Ief
    {
        $ia = Ia::findOrFail($data['ia_id']);

        if (!$ia->est_actif) {
            throw new \DomainException(
                'Impossible de créer une IEF rattachée à une IA inactive.'
            );
        }

        $data['est_actif'] = true;

        $ief = Ief::create($data);

        return $ief->fresh(['ia']);
    }
}