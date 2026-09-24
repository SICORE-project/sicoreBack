<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use Illuminate\Support\Facades\DB;

class IaPayrollReports
{
    public function __construct(private IaScope $scope) {}

    public function report(User $user, ?int $periodId, string $view): ?array
    {
        $slips = $this->scope->payslips($user)->where('p.payroll_period_id', $periodId ?? 0);
        if ($view === 'contributions') {
            $rows = DB::table('payroll_payslip_lines as l')->whereIn('l.payroll_payslip_id', (clone $slips)->select('p.id'))
                ->where(fn ($q) => $q->whereIn('l.category', ['contribution', 'employer_contribution'])
                    ->orWhereIn('l.code', ['COTISATION_SOCIALE', 'IPRES_SALARIE']))
                ->select('l.code', 'l.label', 'l.category')->selectRaw('COUNT(DISTINCT l.payroll_payslip_id) as agents, SUM(l.amount) as montant')
                ->groupBy('l.code', 'l.label', 'l.category')->orderBy('l.code')->get();

            return ['columns' => ['Code', 'Cotisation', 'Part', 'Agents', 'Montant (FCFA)'],
                'rows' => $rows->map(fn ($row) => [$row->code, $row->label,
                    $row->category === 'employer_contribution' ? 'Employeur' : 'Salariale', $row->agents, $row->montant])->all()];
        }
        if ($view === 'workforce') {
            $iaId = $this->scope->id($user);
            $rows = $this->scope->teachers($user)->leftJoin('iefs as i', fn ($join) => $join->on('i.id', '=', 'e.ief_id')
                ->where('i.ia_id', $iaId)->whereNull('i.deleted_at'))
                ->select('i.libelle')->selectRaw("COUNT(*) as agents, SUM(CASE WHEN e.type_engagement = 'fonctionnaire' THEN 1 ELSE 0 END) as fonctionnaires, SUM(CASE WHEN e.type_engagement IN ('contractuel', 'vacataire') THEN 1 ELSE 0 END) as non_fonctionnaires")
                ->groupBy('i.id', 'i.libelle')->orderBy('i.libelle')->get();

            return ['columns' => ['IEF', 'Agents', 'Fonctionnaires', 'Contractuels et vacataires'],
                'rows' => $rows->map(fn ($row) => [$row->libelle ?: 'IEF non renseignée', $row->agents, $row->fonctionnaires, $row->non_fonctionnaires])->all()];
        }
        if ($view === 'banks') {
            // Un seul compte actif par enseignant pour éviter de compter deux fois son bulletin.
            $bank = DB::table('comptes_bancaires_enseignants as c')->select('c.institut_financier_id')
                ->whereColumn('c.enseignant_id', 'e.id')->where('c.est_actif', true)
                ->orderByDesc('c.est_principal')->orderBy('c.id')->limit(1);
            $source = $slips->select('p.id', 'p.gross_amount', 'p.deduction_amount', 'p.net_amount', 'p.payment_status')->selectSub($bank, 'bank_id');
            $rows = DB::query()->fromSub($source, 's')->leftJoin('instituts_financieres as b', 'b.id', '=', 's.bank_id')
                ->select('b.libelle')->selectRaw("COUNT(*) as agents, SUM(s.gross_amount) as brut, SUM(s.deduction_amount) as retenues, SUM(s.net_amount) as net, SUM(CASE WHEN s.payment_status = 'paid' THEN 1 ELSE 0 END) as payes")
                ->groupBy('b.id', 'b.libelle')->orderBy('b.libelle')->get();

            return ['columns' => ['Banque', 'Bulletins', 'Brut (FCFA)', 'Retenues (FCFA)', 'Net (FCFA)', 'Bulletins payés'],
                'rows' => $rows->map(fn ($row) => [$row->libelle ?: 'Banque non renseignée', $row->agents, $row->brut, $row->retenues, $row->net, $row->payes])->all()];
        }

        return null;
    }
}
