<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    protected $fillable = [
        'motif',
        'montant',
        'date_engagement',
        'reference_operation',
        'delegation_credit_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_engagement' => 'date',
        ];
    }

    public function delegationCredit()
    {
        return $this->belongsTo(DelegationCredit::class);
    }
}
