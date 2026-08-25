<?php

namespace App\Services\Parametrage;

use App\Models\Paie\AnneeAcademique;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AnneeAcademiqueService
{
    public function getAll(?string $search = null): Collection
    {
        return AnneeAcademique::query()
            ->when($search, fn ($query, $search) => $query->where('libelle', 'ilike', '%'.trim($search).'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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

    public function update(AnneeAcademique $annee, array $data): AnneeAcademique
    {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Une année académique clôturée ne peut plus être modifiée.',
            );
        }

        $annee->update($data);

        return $annee->fresh();
    }

    public function activate(AnneeAcademique $annee): AnneeAcademique
    {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Une année académique clôturée ne peut pas être activée.',
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

    public function deactivate(AnneeAcademique $annee): AnneeAcademique
    {
        $annee->est_active = false;
        $annee->save();

        return $annee->fresh();
    }

    public function close(AnneeAcademique $annee): AnneeAcademique
    {
        if ($annee->est_cloturee) {
            throw new DomainException(
                'Cette année académique est déjà clôturée.',
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
                'Une année académique active ne peut pas être supprimée.',
            );
        }

        if ($annee->periodesPaie()->exists()) {
            throw new DomainException(
                'Cette année académique possède des périodes de paie et ne peut pas être supprimée.',
            );
        }

        $annee->delete();
    }
}
