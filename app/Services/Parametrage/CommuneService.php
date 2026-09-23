<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Commune;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommuneService
{
    /**
     * Liste paginée des communes.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Commune::query()
            ->with([
                'region',
                'departement',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Recherche
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtre région
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['region_id'])) {
            $query->where(
                'region_id',
                (int) $filters['region_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtre département
        |--------------------------------------------------------------------------
        */
        if (! empty($filters['departement_id'])) {
            $query->where(
                'departement_id',
                (int) $filters['departement_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtre statut
        |--------------------------------------------------------------------------
        */
        if (
            array_key_exists('est_actif', $filters) &&
            $filters['est_actif'] !== '' &&
            $filters['est_actif'] !== null
        ) {
            $estActif = filter_var(
                $filters['est_actif'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($estActif !== null) {
                $query->where('est_actif', $estActif);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Tri
        |--------------------------------------------------------------------------
        */
        $allowedSorts = [
            'id',
            'code',
            'libelle',
            'region_id',
            'departement_id',
            'est_actif',
            'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'libelle';

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'libelle';
        }

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'asc'
        );

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection);

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $perPage = (int) ($filters['per_page'] ?? 10);

        $perPage = max(
            1,
            min($perPage, 100)
        );

        return $query->paginate($perPage);
    }

    /**
     * Récupérer une commune.
     */
    public function findById(int $id): Commune
    {
        return Commune::with([
            'region',
            'departement',
        ])->findOrFail($id);
    }

    /**
     * Créer une commune.
     */
    public function create(array $data): Commune
    {
        // Une nouvelle commune est active par défaut.
        $data['est_actif'] = true;

        $commune = Commune::create($data);

        return $commune->load([
            'region',
            'departement',
        ]);
    }

    /**
     * Modifier une commune.
     */
    public function update(int $id, array $data): Commune
    {
        $commune = Commune::findOrFail($id);

        /*
         * Le statut ne doit pas être modifié
         * par la route de modification classique.
         */
        unset($data['est_actif']);

        $commune->update($data);

        return $commune->refresh()->load([
            'region',
            'departement',
        ]);
    }

    /**
     * Activer / désactiver une commune.
     */
    public function changeStatut(
        int $id,
        bool $estActif
    ): Commune {
        $commune = Commune::findOrFail($id);

        $commune->update([
            'est_actif' => $estActif,
        ]);

        return $commune->refresh()->load([
            'region',
            'departement',
        ]);
    }

    /**
     * Vérifier si la commune est utilisée.
     *
     * Pour le moment aucune relation métier dépendante
     * n'est encore définie sur Commune.
     */
    public function isUsed(Commune $commune): bool
    {
        return false;
    }

    /**
     * Supprimer une commune.
     */
    public function delete(int $id): void
    {
        $commune = Commune::findOrFail($id);

        if ($this->isUsed($commune)) {
            throw new DomainException(
                'Cette commune ne peut pas être supprimée car elle est utilisée.'
            );
        }

        $commune->delete();
    }
}