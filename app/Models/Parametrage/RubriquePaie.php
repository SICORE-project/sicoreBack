<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrage\Corps;

class RubriquePaie extends Model
{
    use HasFactory;

    protected $table = 'rubrique_paies';

    protected $fillable = [
        'code',
        'libelle',
        'type',
        'sens',
        'periodicite',
        'est_cotisable',
        'est_imposable',
        'est_afficher_bulletin',
        'taux_defaut',
        'montant_defaut',
        'formule_calcul',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'est_cotisable' => 'boolean',
        'est_imposable' => 'boolean',
        'est_afficher_bulletin' => 'boolean',
        'taux_defaut' => 'decimal:2',
        'montant_defaut' => 'decimal:2',
    ];

    // === RELATIONS ===
    public function corps()
    {
        return $this->belongsToMany(Corps::class, 'rubrique_par_corps')
                    ->withPivot('taux_personnalise', 'montant_personnalise', 'est_applicable', 'formule_personnalisee', 'est_actif')
                    ->withTimestamps();
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCrediteur($query)
    {
        return $query->where('sens', 'crediteur');
    }

    public function scopeDebiteur($query)
    {
        return $query->where('sens', 'debiteur');
    }

    // === ACCESSORS ===
    public function getTypeLibelleAttribute()
    {
        $types = [
            'salaire_base' => 'Salaire de base',
            'indemnite_logement' => 'Indemnité de logement',
            'indemnite_transport' => 'Indemnité de transport',
            'indemnite_sujetion' => 'Indemnité de sujétion',
            'prime_rendement' => 'Prime de rendement',
            'prime_anciennete' => "Prime d'ancienneté",
            'prime_expatriation' => "Prime d'expatriation",
            'avantage_en_nature' => 'Avantage en nature',
            'retenue_cnss' => 'Retenue CNSS',
            'retenue_impot' => "Retenue d'impôt",
            'retenue_syndicale' => 'Retenue syndicale',
            'cotisation_pension' => 'Cotisation pension',
            'cotisation_assurance' => 'Cotisation assurance',
            'autre' => 'Autre',
        ];
        return $types[$this->type] ?? $this->type;
    }

    public function getSensLibelleAttribute()
    {
        return $this->sens === 'crediteur' ? 'Créditeur' : 'Débiteur';
    }
}