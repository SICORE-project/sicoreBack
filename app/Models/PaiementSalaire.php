<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementSalaire extends Model
{
   protected $table = 'paiement_salaires';

    protected $fillable = [
        'delegation_credit_id',
        'nom_agent',
        'mois',
        'montant',
        'date_paiement'
    ];

    public function delegationCredit()
    {
        return $this->belongsTo(DelegationCredit::class);
    } 
}
