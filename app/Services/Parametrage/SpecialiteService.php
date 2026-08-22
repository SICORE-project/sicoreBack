<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Specialite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SpecialiteService
{

    public function create(array $data): Specialite
    {
        $data['est_actif'] = $data['est_actif'] ?? true;

        return Specialite::create($data);
    }


    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Specialite::query();

        // Recherche par code ou libellé
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        // Filtre actif / inactif
        if (isset($filters['est_actif']) && $filters['est_actif'] !== '') {
            $query->where(
                'est_actif',
                filter_var($filters['est_actif'], FILTER_VALIDATE_BOOLEAN)
            );
        }

        // Tri autorisé uniquement sur code ou libellé
        $sortBy = in_array($filters['sort_by'] ?? null, ['code', 'libelle'])
            ? $filters['sort_by']
            : 'libelle';

        $sortDirection = strtolower($filters['sort_direction'] ?? 'asc');

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
    }

    public function findById(int $id): Specialite
{
    return Specialite::findOrFail($id);
}

public function update(int $id, array $data): Specialite
{
    $specialite = $this->findById($id);

    $specialite->update($data);

    return $specialite->refresh();

}

public function changeStatus(int $id, bool $estActif): Specialite
{
    $specialite = $this->findById($id);

    $specialite->update([
        'est_actif' => $estActif,
    ]);

    return $specialite->refresh();
}

public function getActives()
{
    return Specialite::query()
        ->where('est_actif', true)
        ->orderBy('libelle')
        ->get();
}
}