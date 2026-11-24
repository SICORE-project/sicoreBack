<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enseignant extends Model
{
    use \App\Models\Concerns\HasTeacherEmploymentStatus, \App\Models\Concerns\DerivesTeacherIaFromIef;

    protected $table = 'enseignants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_recrutement' => 'date',
            'salaire_base' => 'decimal:2',
            'impr_monthly_amount' => 'decimal:2',
            'trimf_monthly_amount' => 'decimal:2',
            'ipm_monthly_amount' => 'decimal:2',
            'union_checkoff_monthly_amount' => 'decimal:2',
            'actif' => 'boolean',
            'imported_at' => 'datetime',
            'payroll_profile_configured_at' => 'datetime',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'enseignant_id');
    }

    public function institutionFinanciere(): BelongsTo
    {
        return $this->belongsTo(institution_financieres::class, 'institution_financiere_id');
    }

    public function mutuelle(): BelongsTo
    {
        return $this->belongsTo(mutuelles::class, 'mutuelle_id');
    }

    public function corps(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\CorpsEnseignant::class, 'corps_id');
    }

    public function statutEnseignant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\StatutEnseignant::class, 'statut_enseignant_id');
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(etablissements::class, 'etablissement_id');
    }

    public function ief(): BelongsTo
    {
        return $this->belongsTo(iefs::class, 'ief_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PayrollAttendance::class);
    }

    public function payrollElements(): HasMany
    {
        return $this->hasMany(PayrollElement::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayrollPayslip::class);
    }

    /**
     * Règle unique d'éligibilité à une période de paie, partagée par le calcul,
     * l'Édition et « Paie non générée » :
     * - enseignant actif et non supprimé ;
     * - entré en service (prise de service, à défaut recrutement) au plus tard le dernier jour ;
     * - contrat non terminé avant le premier jour de la période.
     * Un enseignant sans aucune date d'entrée n'est jamais éligible.
     */
    public function scopeEligiblePourPeriode(Builder $query, PayrollPeriod $period): Builder
    {
        // Colonnes vérifiées comme ailleurs dans la paie (schémas historiques ou de test).
        $has = fn (string $column): bool => Schema::hasColumn('enseignants', $column);

        $query->where('enseignants.'.($has('est_actif') ? 'est_actif' : 'actif'), true);
        if ($has('deleted_at')) {
            $query->whereNull('enseignants.deleted_at');
        }
        $startColumns = array_values(array_filter(['date_prise_service', 'date_recrutement'], $has));
        if ($startColumns !== []) {
            $start = count($startColumns) > 1
                ? 'COALESCE('.implode(', ', array_map(fn ($c) => 'enseignants.'.$c, $startColumns)).')'
                : 'enseignants.'.$startColumns[0];
            $query->whereRaw("DATE({$start}) <= ?", [$period->end_date->toDateString()]);
        }
        if ($has('date_fin_contrat')) {
            $query->where(function (Builder $q) use ($period): void {
                $q->whereNull('enseignants.date_fin_contrat')
                    ->orWhereDate('enseignants.date_fin_contrat', '>=', $period->start_date->toDateString());
            });
        }

        return $query;
    }

    /** Enseignants payés par le module : statut contractuel (PC) ou vacataire (VAC). */
    public function scopeContractuelOuVacataire(Builder $query): Builder
    {
        $statuses = Schema::hasTable('statuts_enseignant')
            ? DB::table('statuts_enseignant')->whereIn('code', ['PC', 'VAC'])
                ->orWhereRaw('LOWER(libelle) LIKE ?', ['%contractuel%'])
                ->orWhereRaw('LOWER(libelle) LIKE ?', ['%vacataire%'])
                ->pluck('id')
            : collect();

        return $query->where(function (Builder $q) use ($statuses): void {
            $q->whereIn('enseignants.statut_enseignant_id', $statuses)
                ->orWhereIn('enseignants.type_engagement', ['contractuel', 'vacataire']);
        });
    }
}
