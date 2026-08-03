<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DelegationCredit extends Model
{
    protected $table = 'delegation_credits';

    protected $fillable = [
        'annee_academique',
        'reference',
        'objet',
        'structure_id',
        'service_id',
        'montant_disponible',
        'montant_engage',
        'montant_consomme',
        'solde',
        'date_delegation',
        'statut'
    ];

    // Une délégation appartient à une structure
    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }

    // Une délégation appartient à un service
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Une délégation possède plusieurs paiements
    public function paiementSalaires()
    {
        return $this->hasMany(PaiementSalaire::class);
    }
}
