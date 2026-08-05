<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodePaie extends Model
{
    use HasFactory;

    protected $table = 'periode_de_paies';

    protected $fillable = [
        'libelle',
        'mois',
        'annee',
        'date_debut',
        'date_fin',
        'date_paiement',
        'date_limite_saisie',
        'date_limite_validation',
        'est_fermee',
        'est_active',
        'est_verrouillee',
        'annee_academique_id',
        'observations',
    ];

    protected $casts = [
        'est_fermee' => 'boolean',
        'est_active' => 'boolean',
        'est_verrouillee' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_paiement' => 'date',
        'date_limite_saisie' => 'date',
        'date_limite_validation' => 'date',
        'mois' => 'integer',
        'annee' => 'integer',
    ];

    // === RELATIONS ===
    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    // === SCOPES ===
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeFermee($query)
    {
        return $query->where('est_fermee', true);
    }

    public function scopeVerrouillee($query)
    {
        return $query->where('est_verrouillee', true);
    }

    public function scopeByAnnee($query, $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopeByMois($query, $mois)
    {
        return $query->where('mois', $mois);
    }

    // === ACCESSORS ===
    public function getLibelleCompletAttribute()
    {
        return $this->libelle . ' (' . $this->mois . '/' . $this->annee . ')';
    }

    public function getNomMoisAttribute()
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $mois[$this->mois] ?? '';
    }
}