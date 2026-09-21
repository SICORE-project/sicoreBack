<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use Illuminate\Support\Facades\DB;

class IaScope
{
    public function id(User $user): int
    {
        $structure = $user->lieuService;
        abort_unless($user->ia_id && $structure && $structure->est_actif
            && strtoupper($structure->type) === 'IA'
            && (int) $structure->ia_id === (int) $user->ia_id, 403);
        abort_unless(DB::table('ias')->where('id', $user->ia_id)->whereNull('deleted_at')->exists(), 403);
        return (int) $user->ia_id;
    }

    public function teachers(User $user)
    {
        return DB::table('enseignants as e')->where('e.ia_id', $this->id($user))->whereNull('e.deleted_at');
    }

    public function payslips(User $user)
    {
        return DB::table('payroll_payslips as p')->join('enseignants as e', 'e.id', '=', 'p.enseignant_id')
            ->where('e.ia_id', $this->id($user))->whereNull('e.deleted_at');
    }
}
