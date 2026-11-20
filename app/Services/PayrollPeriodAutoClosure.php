<?php

namespace App\Services;

/** Compatibility for former workers: no automatic status changes or locks. */
class PayrollPeriodAutoClosure
{
    public function run(): array
    {
        return ['closed' => 0, 'failed' => 0];
    }
}