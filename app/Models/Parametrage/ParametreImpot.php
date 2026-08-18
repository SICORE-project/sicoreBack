<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreImpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'annee',
        'abattement_general',
        'plafond_cnss',
        'taux_cnss',
        'taux_impot_min',
        'taux_impot_max',
        'seuil_exoneration',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'annee' => 'integer',
        'abattement_general' => 'decimal:2',
        'plafond_cnss' => 'decimal:2',
        'taux_cnss' => 'decimal:2',
        'taux_impot_min' => 'decimal:2',
        'taux_impot_max' => 'decimal:2',
        'seuil_exoneration' => 'decimal:2',
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
}