<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DelegationCredit extends Model
{
    protected $table = 'delegation_credits';

    protected $fillable = [
        'annee_academique',
        'periode_paie',
        'reference',
        'objet',
        'structure_id',
        'service_id',
        'montant_initial',
        'montant_disponible',
        'montant_engage',
        'montant_consomme',
        'solde',
        'date_delegation',
        'date_fin',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_delegation' => 'date',
            'date_fin' => 'date',
            'montant_initial' => 'decimal:2',
            'montant_disponible' => 'decimal:2',
            'montant_engage' => 'decimal:2',
            'montant_consomme' => 'decimal:2',
            'solde' => 'decimal:2',
        ];
    }

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

    public function paiementSalaires()
    {
        return $this->hasMany(PaiementSalaire::class);
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    // Une delegation se ventile en N lignes (ecran FINPRONET "Ventilations")
    public function ventilations()
    {
        return $this->hasMany(VentilationDelegation::class);
    }
}
