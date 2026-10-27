<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollGenerationService
{
    public function generate(array $data, User $actor, Request $request, ?string $key): PayrollRun
    {
        return DB::transaction(function () use ($data, $actor, $request, $key) {
            $year = DB::table('annee_academiques')->find($data['annee_academique_id']);
            if (! $year || $year->est_cloturee) {
                throw ValidationException::withMessages(['annee_academique_id' => 'Choisissez une année académique non clôturée dans Paramétrage.']);
            }
            $dates = collect();
            $date = CarbonImmutable::parse($year->date_debut)->startOfMonth();
            $end = CarbonImmutable::parse($year->date_fin)->endOfMonth();
            while ($date->lessThanOrEqualTo($end)) {
                if ($date->month === (int) $data['month']) { $dates->push($date->format('Y-m')); }
                $date = $date->addMonth();
            }
            if ($dates->count() !== 1) {
                throw ValidationException::withMessages(['month' => 'Le mois doit identifier une seule période dans les dates de l’année académique.']);
            }
            $codes = app(PayrollPeriodCatalogue::class)->synchronize();
            $period = PayrollPeriod::whereIn('code', $codes)->where('code', $dates->first())->first();
            if (! $period) {
                throw ValidationException::withMessages(['month' => 'Créez ce mois dans le CRUD Périodes de paie de Paramétrage.']);
            }
            $query = Enseignant::query()->where('est_actif', true)->whereNull('deleted_at');
            if ($data['generation_scope'] === 'teacher') {
                $query->where('id', $data['enseignant_id']);
            } else {
                $iaId = (int) $data['ia_id'];
                $query->where(function ($q) use ($iaId) {
                    $q->where('ia_id', $iaId)->orWhere(function ($fallback) use ($iaId) {
                        $fallback->whereNull('ia_id')->whereIn('centre_formation_id', DB::table('centres_formation')->where('ia_id', $iaId)->select('id'));
                    });
                });
                if (! empty($data['ief_id'])) {
                    $iefId = (int) $data['ief_id'];
                    $query->where(function ($q) use ($iefId) {
                        $q->where('ief_id', $iefId)->orWhere(function ($fallback) use ($iefId) {
                            $fallback->whereNull('ief_id')->whereIn('centre_formation_id', DB::table('centres_formation')->where('ief_id', $iefId)->select('id'));
                        });
                    });
                }
                if (! empty($data['statut_enseignant_id'])) {
                    $query->where('statut_enseignant_id', $data['statut_enseignant_id']);
                }
                $query->where(function ($q) {
                    $q->whereIn('type_engagement', ['contractuel', 'vacataire'])->orWhereIn('statut_enseignant_id',
                        DB::table('statuts_enseignant')->whereIn('code', ['PC', 'VAC'])
                            ->orWhereRaw('LOWER(libelle) LIKE ?', ['%contractuel%'])
                            ->orWhereRaw('LOWER(libelle) LIKE ?', ['%vacataire%'])->select('id'));
                });
            }
            $ids = $query->orderBy('id')->pluck('id')->all();
            if ($ids === []) {
                throw ValidationException::withMessages(['selection' => 'Aucun enseignant actif PC/VAC ne correspond à cette sélection.']);
            }
            $run = app(PayrollCalculationService::class)->calculate($period, $actor, $ids);
            app(PayrollAuditService::class)->log('payroll.generated', $run, null, [
                ...$run->toArray(), 'selection' => $data, 'teacher_ids' => $ids, 'selected_count' => count($ids),
            ], $actor, $request, $key);
            return $run;
        }, 3);
    }
}
