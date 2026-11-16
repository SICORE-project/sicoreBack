<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollPeriodAutoClosure
{
    public function run(): array
    {
        $result = ['closed' => 0, 'failed' => 0];
        // End date is inclusive: closure starts at midnight the following day.
        $ids = DB::table('periode_de_paies')->whereNotNull('payroll_period_id')
            ->whereDate('date_fin', '<', today()->toDateString())->pluck('payroll_period_id')->unique();
        foreach (PayrollPeriod::whereIn('id', $ids)->where('status', '!=', 'closed')->with('run')->get() as $period) {
            try {
                $actor = User::find($period->run?->calculated_by);
                if (! $actor) throw new \RuntimeException('Auteur du calcul introuvable.');
                app(PayrollPeriodLifecycle::class)->close($period->id, $actor, Request::create('/system/payroll/auto-close', 'POST'));
                $result['closed']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::error('Échec de clôture automatique de paie', ['period_id' => $period->id, 'error' => $e->getMessage()]);
            }
        }
        return $result;
    }
}
