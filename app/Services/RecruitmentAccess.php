<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Services\Administration\Personnel\DrhScope;
use Illuminate\Support\Facades\DB;

class RecruitmentAccess
{
    public function members(User $user)
    {
        $query = DB::table('recruitment_members as m')->join('enseignants as e', 'e.id', '=', 'm.enseignant_id')
            ->join('recruitment_batches as b', 'b.id', '=', 'm.batch_id')->whereNull('e.deleted_at');
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        $structure = $user->lieuService;
        if (! $structure || ! $structure->est_actif) {
            return $query->whereRaw('1=0');
        }
        if (in_array(strtoupper($structure->type), ['DAGE', 'IA', 'IEF'], true)) {
            $query->whereNotNull('b.transmitted_at');
        }
        $scope = app(DrhScope::class)->describe($user);

        return match ($scope['type']) {
            'national' => $query,
            'aucun' => $query->whereRaw('1=0'),
            default => $query->where('e.'.$scope['type'], $scope['id']),
        };
    }

    public function batch(User $user, int $id): object
    {
        $batch = DB::table('recruitment_batches')->find($id);
        abort_unless($batch, 404);
        abort_unless($user->hasRole('super_admin') || $this->members($user)->where('m.batch_id', $id)->exists(), 404);

        return $batch;
    }

    public function manageBatch(User $user, int $id): object
    {
        $batch = $this->batch($user, $id);
        $visible = $this->members($user)->where('m.batch_id', $id)->count();
        abort_unless($visible === DB::table('recruitment_members')->where('batch_id', $id)->count(), 403);

        return $batch;
    }
}
