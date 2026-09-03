<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_employer_contributions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'calculated_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayrollPayslip::class);
    }
}
