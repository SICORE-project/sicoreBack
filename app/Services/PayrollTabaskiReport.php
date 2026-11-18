<?php

namespace App\Services;

use App\Models\PayrollElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollTabaskiReport
{
    public function report(string $code, Collection $teachers): array
    {
        $deduction = $code === 'TABASKI_RETENUE';
        $allocations = DB::table('payroll_tabaski_allocations')->whereIn('enseignant_id', $teachers->pluck('id'))
            ->when($deduction, fn ($q) => $q->whereNotNull('repayment_from_month'))->orderByDesc('id')->get();
        $counts = DB::table('payroll_tabaski_installments')->selectRaw('allocation_id, COUNT(*) as number')->groupBy('allocation_id')->pluck('number', 'allocation_id');
        $byTeacher = $teachers->keyBy('id');
        $ias = DB::table('ias')->pluck('libelle', 'id');
        $iefs = DB::table('iefs')->pluck('libelle', 'id');
        $statuses = DB::table('statuts_enseignant')->pluck('libelle', 'id');
        $money = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
        $rows = $allocations->map(function ($a) use ($deduction, $byTeacher, $counts, $ias, $iefs, $statuses, $money) {
            $t = $byTeacher[$a->enseignant_id];
            $count = (int) $counts->get($a->id, 0);
            return [trim(($t->user?->prenom ?? $t->prenom).' '.($t->user?->nom ?? $t->nom)), $t->matricule,
                $statuses->get($a->statut_enseignant_id, '—'), $ias->get($a->ia_id, '—').' / '.$iefs->get($a->ief_id, '—'),
                substr($deduction ? $a->repayment_from_month : $a->advance_from_month, 0, 7),
                $money($deduction ? 10000 : 100000), $deduction ? $count.' / 10' : ($a->advance_period_id ? 'Imputée au bulletin' : 'Programmée'),
                $money(100000 - $count * 10000),
                $count >= 10 ? 'Remboursée' : ($a->repayment_from_month ? 'Échéancier actif' : 'Retenue non activée')];
        });
        // Keep legacy financial history visible; do not invent an advance for an old isolated deduction.
        $references = DB::table('payroll_tabaski_allocations')->pluck('batch_reference');
        $legacy = PayrollElement::with('period')->where('code', $code)->whereIn('enseignant_id', $teachers->pluck('id'))
            ->where(fn ($q) => $q->whereNull('application_reference')->orWhereNotIn('application_reference', $references))->get();
        foreach ($legacy as $element) {
            $t = $byTeacher[$element->enseignant_id];
            $rows->push([trim(($t->user?->prenom ?? $t->prenom).' '.($t->user?->nom ?? $t->nom)), $t->matricule,
                $statuses->get($t->statut_enseignant_id, '—'), $ias->get($t->ia_id, '—').' / '.$iefs->get($t->ief_id, '—'), $element->period?->code ?? '—',
                $money($element->amount), '—', '—', 'Historique antérieur — hors échéancier']);
        }
        $amount = $deduction ? $counts->only($allocations->pluck('id')->all())->sum() * 10000 : $allocations->whereNotNull('advance_period_id')->count() * 100000;
        return [
            'columns' => ['Enseignant', 'Matricule', 'Statut enseignant', 'IA / IEF', 'À partir du mois', 'Montant', 'Échéances / versement', 'Reste à retenir', 'Situation'],
            'tabaski_beneficiaries' => DB::table('payroll_tabaski_allocations')->whereIn('enseignant_id', $teachers->pluck('id'))->whereNull('repayment_from_month')
                ->get(['enseignant_id', 'ia_id', 'ief_id', 'statut_enseignant_id'])->values(),
            'rows' => $rows->values(),
            'actions' => [['label' => 'Appliquer collectivement', 'code' => $deduction ? 'apply-tabaski-deduction' : 'apply-tabaski-advance', 'style' => 'primary'],
                ['label' => 'Exporter CSV', 'code' => 'export', 'style' => 'secondary']],
            'stats' => [
                ['label' => 'Bénéficiaires', 'value' => $allocations->count(), 'note' => 'Enseignants figés à l’attribution', 'color' => 'green', 'icon' => 'EN'],
                ['label' => $deduction ? 'Déjà retenu' : 'Avances sur bulletins', 'value' => $money($amount), 'note' => 'Échéanciers enregistrés', 'color' => 'blue', 'icon' => 'FC'],
            ],
            'notice' => 'Avance : 100 000 FCFA sur le prochain bulletin éligible. Retenue : 10 échéances de 10 000 FCFA, activées uniquement pour les bénéficiaires. Aucun enseignant ajouté ensuite ne rejoint une avance déjà attribuée. L’historique antérieur n’est pas converti automatiquement.',
        ];
    }
}
