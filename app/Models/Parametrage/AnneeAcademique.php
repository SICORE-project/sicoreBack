<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnneeAcademique extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'intitule_court',
        'est_active',
        'est_cloturee',
        'date_cloture',
        'observations',
    ];

    protected $casts = [
        'est_active' => 'boolean',
        'est_cloturee' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_cloture' => 'date',
    ];

    // === RELATIONS ===
    public function periodesPaie()
    {
        return $this->hasMany(PeriodePaie::class);
    }

    public function parametresBudgetaires()
    {
        return $this->hasMany(ParametreBudgetaire::class);
    }

    public function datesButoirs()
    {
        return $this->hasMany(DateButoir::class);
    }

    // === SCOPES ===
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeCloturee($query)
    {
        return $query->where('est_cloturee', true);
    }

    // === ACCESSORS ===
    public function getLibelleCompletAttribute()
    {
        return $this->libelle . ' (' . $this->date_debut->format('d/m/Y') . ' - ' . $this->date_fin->format('d/m/Y') . ')';
    }
}