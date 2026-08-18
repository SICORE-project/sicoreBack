<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementSalaire extends Model
{
   protected $table = 'paiement_salaires';

    protected $fillable = [
        'delegation_credit_id',
        'bultin_id',
        'nom_agent',
        'mois',
        'montant',
        'date_paiement',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'date',
        ];
    }

    public function delegationCredit()
    {
        return $this->belongsTo(DelegationCredit::class);
    }

    public function bultin()
    {
        return $this->belongsTo(bultins::class, 'bultin_id');
    }
}
