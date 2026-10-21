<?php

namespace App\Console\Commands;

use App\Models\Admin\User;
use App\Services\RecruitmentAccess;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecruitmentAlerts extends Command
{
    protected $signature = 'recruitment:alerts';

    protected $description = 'Signaler les échéances de deux ans sans changer le statut';

    public function handle(): int
    {
        $recipients = User::where('statut', 'actif')->whereHas('role.permissions', fn ($q) => $q->where('slug', 'recruitment.transition'))->get();
        $count = 0;
        DB::table('recruitment_members')->where('engagement', 'vacataire')->whereNull('alerted_at')
            ->whereNotNull('service_date')->orderBy('id')->chunkById(100, function ($members) use ($recipients, &$count) {
                foreach ($members as $member) {
                    if (Carbon::parse($member->service_date)->addYearsNoOverflow(2)->isAfter(today())) {
                        continue;
                    }
                    DB::transaction(function () use ($member, $recipients, &$count) {
                        $locked = DB::table('recruitment_members')->where('id', $member->id)->lockForUpdate()->first();
                        if ($locked->alerted_at || $locked->engagement !== 'vacataire') {
                            return;
                        }
                        $notified = false;
                        foreach ($recipients as $user) {
                            if (! app(RecruitmentAccess::class)->members($user)->where('m.id', $member->id)->exists()) {
                                continue;
                            }
                            DB::table('recruitment_notices')->insert(['user_id' => $user->id, 'batch_id' => $member->batch_id,
                                'message' => 'Échéance de deux ans : dossier '.$member->enseignant_id.' à examiner. Décision justificative requise.', 'created_at' => now()]);
                            $notified = true;
                        }
                        if ($notified) {
                            DB::table('recruitment_members')->where('id', $member->id)->update(['alerted_at' => now()]);
                            $count++;
                        }
                    });
                }
            });
        $this->info($count.' dossier(s) signalé(s).');

        return self::SUCCESS;
    }
}
