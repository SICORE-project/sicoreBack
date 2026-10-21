<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Departement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepartementService
{
    /*
    |--------------------------------------------------------------------------
    | LISTE DES DÉPARTEMENTS
    |--------------------------------------------------------------------------
    */

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Departement::query()
            ->with('region');


        /*
        |--------------------------------------------------------------------------
        | RECHERCHE
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['search'])) {

            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");

            });
        }


        /*
        |--------------------------------------------------------------------------
        | FILTRE PAR RÉGION
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
        | FILTRE PAR STATUT
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['est_actif']) &&
            $filters['est_actif'] !== ''
        ) {

            $query->where(
                'est_actif',
                filter_var(
                    $filters['est_actif'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TRI
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'code',
            'libelle',
            'region_id',
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


        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'asc'
        );


        if (! in_array(
            $sortDirection,
            ['asc', 'desc'],
            true
        )) {
            $sortDirection = 'asc';
        }


        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $perPage = (int) (
            $filters['per_page'] ?? 15
        );

        $perPage = max(
            1,
            min($perPage, 100)
        );


        return $query
            ->orderBy(
                $sortBy,
                $sortDirection
            )
            ->paginate($perPage);
    }


    /*
    |--------------------------------------------------------------------------
    | DÉTAIL D'UN DÉPARTEMENT
    |--------------------------------------------------------------------------
    */

    public function findById(int $id): Departement
    {
        return Departement::query()
            ->with('region')
            ->findOrFail($id);
    }


    /*
    |--------------------------------------------------------------------------
    | CRÉATION
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Departement
    {
        return DB::transaction(function () use ($data) {

            /*
            | Une nouvelle entrée est toujours active.
            */
            $data['est_actif'] = true;


            $departement = Departement::query()
                ->create($data);


            return $departement
                ->load('region');
        });
    }


    /*
    |--------------------------------------------------------------------------
    | MODIFICATION
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): Departement {

        return DB::transaction(
            function () use ($id, $data) {

                $departement =
                    $this->findById($id);


                /*
                | Le statut n'est pas modifié ici.
                |
                | Il possède son endpoint dédié.
                */
                unset($data['est_actif']);


                $departement->update($data);


                return $departement
                    ->refresh()
                    ->load('region');
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGEMENT DE STATUT
    |--------------------------------------------------------------------------
    */

    public function changeStatut(
        int $id,
        bool $estActif
    ): Departement {

        return DB::transaction(
            function () use (
                $id,
                $estActif
            ) {

                $departement =
                    $this->findById($id);


                $departement->update([
                    'est_actif' => $estActif,
                ]);


                return $departement
                    ->refresh()
                    ->load('region');
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VÉRIFIER SI LE DÉPARTEMENT EST UTILISÉ
    |--------------------------------------------------------------------------
    */

    public function isUsed(
        Departement $departement
    ): bool {

        return $departement
            ->centresFormation()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | SUPPRESSION
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {

            $departement =
                $this->findById($id);


            if ($this->isUsed($departement)) {

                throw new \DomainException(
                    'Ce département ne peut pas être supprimé car il est déjà utilisé.'
                );
            }


            $departement->delete();
        });
    }
}