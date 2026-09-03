<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAllowanceRate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
