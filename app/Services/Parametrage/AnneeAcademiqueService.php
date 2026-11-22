<?php

namespace App\Services\Parametrage;

use App\Models\Paie\AnneeAcademique;
use App\Models\PayrollPeriod;
use App\Services\PayrollMonthResolver;
use Carbon\CarbonImmutable;
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

        $this->assertPeriodsStillInside($annee, $data);
        $annee->update($data);

        return $annee->fresh();
    }

    /**
     * Les périodes déjà rattachées doivent rester dans l'année après une
     * modification des dates (même règle de chevauchement qu'à l'ouverture).
     */
    private function assertPeriodsStillInside(AnneeAcademique $annee, array $data): void
    {
        if (! array_key_exists('date_debut', $data) && ! array_key_exists('date_fin', $data)) {
            return;
        }
        $dates = (object) [
            'date_debut' => $data['date_debut'] ?? $annee->date_debut,
            'date_fin' => $data['date_fin'] ?? $annee->date_fin,
        ];
        $resolver = app(PayrollMonthResolver::class);
        $outside = PayrollPeriod::where('annee_academique_id', $annee->id)->orderBy('start_date')->get()
            ->reject(fn (PayrollPeriod $p) => $resolver->overlaps($dates, CarbonImmutable::parse($p->start_date), CarbonImmutable::parse($p->end_date)))
            ->pluck('code');
        if ($outside->isNotEmpty()) {
            throw new DomainException(
                'Ces dates excluraient des périodes de paie déjà rattachées à cette année : '.$outside->implode(', ').'.',
            );
        }
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

        $hasPayrollPeriods = PayrollPeriod::where('annee_academique_id', $annee->id)->exists();

        if ($hasPayrollPeriods || DB::table('payroll_elements')->where('annee_academique_id', $annee->id)->exists()) {
            throw new DomainException(
                'Cette année académique possède des données de paie et ne peut pas être supprimée.',
            );
        }

        $annee->delete();
    }
}
