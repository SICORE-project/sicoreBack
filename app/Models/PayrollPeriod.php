<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollPeriod extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_CLOSED = 'closed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'calculated_at' => 'datetime',
            'validated_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PayrollAttendance::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(PayrollElement::class);
    }

    public function run(): HasOne
    {
        return $this->hasOne(PayrollRun::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayrollPayslip::class);
    }

    public function isMutable(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
