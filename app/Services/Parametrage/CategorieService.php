<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Categorie;

class CategorieService
{
    public function getAll(array $filters = [])
    {
        return Categorie::with('corps')
            ->when(! empty($filters['search']), function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'ilike', "%{$search}%")
                        ->orWhere('libelle', 'ilike', "%{$search}%");
                });
            })
            ->when(! empty($filters['corps_id']), fn ($query) => $query->where('corps_id', $filters['corps_id']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(1, (int) ($filters['per_page'] ?? 10))));
    }

    public function findById(int $id): ?Categorie
    {
        return Categorie::with('corps')->find($id);
    }

    public function create(array $data): Categorie
    {
        return Categorie::create($data);
    }

    public function update(Categorie $categorie, array $data): Categorie
    {
        $categorie->update($data);

        return $categorie->fresh('corps');
    }

    public function delete(Categorie $categorie): void
    {
        $categorie->delete();
    }

}
