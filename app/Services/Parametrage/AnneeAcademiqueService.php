<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\AnneeAcademique;
use DomainException;
use Illuminate\Support\Facades\DB;

class AnneeAcademiqueService
{
    // public function getAll()
    // {
    //     return AnneeAcademique::orderByDesc('date_debut')->get();
    // }
public function getAll(?string $search = null)
{
    return AnneeAcademique::query()

        // 1. Recherche par libellé
        ->when($search, function ($query, $search) {
            $query->where(
                'libelle',
                'like',
                '%' . $search . '%'
            );
        })

        // 2. Tri de la plus récente à la plus ancienne
        ->orderByDesc('date_debut')

        ->get();
}

    public function findById(int $id): ?AnneeAcademique
    {
        return AnneeAcademique::find($id);
    }

    public function create(array $data): AnneeAcademique
    {
        $data['est_active'] = false;
        $data['est_cloturee'] = false;

        return AnneeAcademique::create($data);
    }

    public function update(
        AnneeAcademique $annee,
        array $data
    ): AnneeAcademique {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Une année académique clôturée ne peut plus être modifiée.'
            );
        }

        $annee->update($data);

        return $annee->fresh();
    }

    public function activate(
        AnneeAcademique $annee
    ): AnneeAcademique {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Une année académique clôturée ne peut pas être activée.'
            );
        }

        return DB::transaction(function () use ($annee) {

            AnneeAcademique::where('id', '!=', $annee->id)
                ->where('est_active', true)
                ->update([
                    'est_active' => false,
                ]);

            $annee->est_active = true;
            $annee->save();

            return $annee->fresh();
        });
    }

    public function deactivate(
        AnneeAcademique $annee
    ): AnneeAcademique {
        $annee->est_active = false;
        $annee->save();

        return $annee->fresh();
    }

    public function close(
        AnneeAcademique $annee
    ): AnneeAcademique {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Cette année académique est déjà clôturée.'
            );
        }

        $annee->est_active = false;
        $annee->est_cloturee = true;
        $annee->date_cloture = now();
        $annee->save();

        return $annee->fresh();
    }

    public function delete(AnneeAcademique $annee): void
    {
        if ($annee->est_active) {
            throw new DomainException(
                'Une année académique active ne peut pas être supprimée.'
            );
        }

        $annee->delete();
    }
}