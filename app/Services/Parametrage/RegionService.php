<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Region;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RegionService
{
    /**
     * Liste paginée des régions.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Region::query();

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%")
                    ->orWhere('chef_lieu', 'like', "%{$search}%");
            });
        }

        if (isset($filters['est_actif']) && $filters['est_actif'] !== '') {
            $query->where(
                'est_actif',
                filter_var($filters['est_actif'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        $allowedSorts = [
            'id',
            'code',
            'libelle',
            'chef_lieu',
            'population',
            'superficie',
            'est_actif',
            'created_at',
        ];

        $sortBy = in_array(
            $filters['sort_by'] ?? '',
            $allowedSorts,
            true
        )
            ? $filters['sort_by']
            : 'libelle';

        $sortDirection = strtolower($filters['sort_direction'] ?? 'asc');

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Récupérer une région.
     */
    public function findById(int $id): Region
    {
        return Region::query()->findOrFail($id);
    }

    /**
     * Créer une région.
     */
    public function create(array $data): Region
    {
        return DB::transaction(function () use ($data) {
            $data['est_actif'] = true;

            return Region::query()->create($data);
        });
    }

    /**
     * Modifier une région.
     */
    public function update(int $id, array $data): Region
    {
        return DB::transaction(function () use ($id, $data) {
            $region = $this->findById($id);

            $region->update($data);

            return $region->refresh();
        });
    }

    /**
     * Changer le statut actif/inactif.
     */
    public function changeStatut(int $id, bool $estActif): Region
    {
        return DB::transaction(function () use ($id, $estActif) {
            $region = $this->findById($id);

            $region->update([
                'est_actif' => $estActif,
            ]);

            return $region->refresh();
        });
    }

    /**
     * Vérifier si la région est déjà utilisée.
     */
    public function isUsed(Region $region): bool
    {
        return $region->ias()->exists()
            || $region->departements()->exists()
            || $region->centresFormation()->exists();
    }

    /**
     * Supprimer une région non utilisée.
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $region = $this->findById($id);

            if ($this->isUsed($region)) {
                throw new \DomainException(
                    'Cette région ne peut pas être supprimée car elle est déjà utilisée.'
                );
            }

            $region->delete();
        });
    }
}