<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $allowedSorts = [
            'code',
            'libelle',
            'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'created_at';

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'libelle';
        }

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'asc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection)->orderByDesc('id');

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

        $ief = Ief::create($data);

        return $ief->fresh(['ia']);
    }


    public function getByIa(int $iaId, array $filters = [])
{
    $query = Ief::where('ia_id', $iaId);

    if (!empty($filters['search'])) {
        $search = trim($filters['search']);

        $query->where(function ($q) use ($search) {
            $q->where('code', 'like', '%' . $search . '%')
                ->orWhere('libelle', 'like', '%' . $search . '%');
        });
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
 * Modifier une IEF.
 */
public function update(int $id, array $data): Ief
{
    // Vérifier que l'IEF existe
    $ief = $this->findById($id);

    // Vérifier que l'IA sélectionnée existe
    $ia = Ia::findOrFail($data['ia_id']);

    /*
    | Vérifier s'il y a un changement d'IA
    */

    $changementIa = (int) $ief->ia_id !== (int) $data['ia_id'];

    if ($changementIa) {

        /*
        | Vérifier les lieux de service / établissements rattachés
        */

        if ($ief->lieuxServices()->exists()) {
            throw new \DomainException(
                'Impossible de changer l’IA de cette IEF car des lieux de service y sont rattachés.'
            );
        }

        /*
        | Vérifier les enseignants rattachés
        */

        if ($ief->enseignants()->exists()) {
            throw new \DomainException(
                'Impossible de changer l’IA de cette IEF car des enseignants y sont rattachés.'
            );
        }

        /*
        
        | Vérifier les utilisateurs rattachés
        */

        if ($ief->users()->exists()) {
            throw new \DomainException(
                'Impossible de changer l’IA de cette IEF car des utilisateurs y sont rattachés.'
            );
        }
    }

    /*
    | Mise à jour
    */

    $ief->update($data);

    /*
    | Retourner l'IEF avec son IA
    */

    return $ief->fresh(['ia']);
}

public function delete(int $id): void
{
    $this->findById($id)->delete();
}

public function changeStatus(int $id, bool $newStatus): Ief
{
    // Vérifier l'existence de l'IEF
    $ief = $this->findById($id);

    // Éviter une opération inutile
    if ($ief->est_actif === $newStatus) {
        throw new \DomainException(
            $newStatus
                ? 'Cette IEF est déjà active.'
                : 'Cette IEF est déjà inactive.'
        );
    }

    /*
    | Réactivation
    */
    if ($newStatus) {
        $ia = Ia::findOrFail($ief->ia_id);

        if (!$ia->est_actif) {
            throw new \DomainException(
                'Impossible d’activer cette IEF car son IA de rattachement est inactive.'
            );
        }
    }

    /*
    | Changement de statut
    */

    $ief->est_actif = $newStatus;
    $ief->save();

    return $ief->fresh(['ia']);
}

public function rattacherIa(int $iefId, int $nouvelleIaId): array
{
    $ief = $this->findById($iefId);

    $nouvelleIa = Ia::find($nouvelleIaId);

    if (!$nouvelleIa) {
        throw (new ModelNotFoundException())
            ->setModel(Ia::class, [$nouvelleIaId]);
    }

    if ((int) $ief->ia_id === (int) $nouvelleIaId) {
        throw new \DomainException(
            'Cette IEF est déjà rattachée à l’IA sélectionnée.'
        );
    }

    $ancienneIa = $ief->ia;

    return DB::transaction(function () use (
        $ief,
        $ancienneIa,
        $nouvelleIa,
        $nouvelleIaId
    ) {
        $ief->lieuxServices()->update([
            'ia_id' => $nouvelleIaId,
        ]);

        $ief->enseignants()->update([
            'ia_id' => $nouvelleIaId,
        ]);

        $ief->users()->update([
            'ia_id' => $nouvelleIaId,
        ]);

        $ief->ia_id = $nouvelleIaId;
        $ief->save();

        return [
            'ief' => $ief->fresh(['ia']),
            'ancienne_ia' => $ancienneIa,
            'nouvelle_ia' => $nouvelleIa,
        ];
    });
}

}
