<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpotRevenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'tranche_min',
        'tranche_max',
        'taux',
        'montant_fixe',
        'annee',
        'date_debut',
        'date_fin',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'tranche_min' => 'decimal:2',
        'tranche_max' => 'decimal:2',
        'taux' => 'decimal:2',
        'montant_fixe' => 'decimal:2',
        'annee' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByAnnee($query, $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopeByMontant($query, $montant)
    {
        return $query->where('tranche_min', '<=', $montant)
                     ->where(function($q) use ($montant) {
                         $q->whereNull('tranche_max')
                           ->orWhere('tranche_max', '>=', $montant);
                     });
    }
}