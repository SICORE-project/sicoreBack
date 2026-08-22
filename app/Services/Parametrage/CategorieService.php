<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Categorie;

class CategorieService
{
    public function getAll()
    {
        return Categorie::with('corps')
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get();
    }

    public function findById(int $id): ?Categorie
    {
        return Categorie::with('corps')->find($id);
    }

    public function create(array $data): Categorie
    {
        $data['est_actif'] = true;

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

    public function activate(Categorie $categorie): Categorie
    {
        $categorie->est_actif = true;
        $categorie->save();

        return $categorie->fresh('corps');
    }

    public function deactivate(Categorie $categorie): Categorie
    {
        $categorie->est_actif = false;
        $categorie->save();

        return $categorie->fresh('corps');
    }
}