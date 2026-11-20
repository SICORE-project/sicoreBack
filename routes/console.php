<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('recruitment:alerts')->daily()->withoutOverlapping();
Artisan::command('payroll:close-expired', function () {
    $result = app(\App\Services\PayrollPeriodAutoClosure::class)->run();
    $this->info('La clôture automatique des périodes de paie est désactivée.');
    return $result['failed'] ? 1 : 0;
})->purpose('Clôturer les périodes de paie après leur date de fin');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
