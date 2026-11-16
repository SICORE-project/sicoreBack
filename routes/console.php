<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('recruitment:alerts')->daily()->withoutOverlapping();
Artisan::command('payroll:close-expired', function () {
    $result = app(\App\Services\PayrollPeriodAutoClosure::class)->run();
    $this->info($result['closed'].' période(s) clôturée(s), '.$result['failed'].' échec(s).');
    return $result['failed'] ? 1 : 0;
})->purpose('Clôturer les périodes de paie après leur date de fin');
Schedule::command('payroll:close-expired')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
