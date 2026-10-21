<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollParameter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
