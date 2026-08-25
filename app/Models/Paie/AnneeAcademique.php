<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnneeAcademique extends Model
{
    use HasFactory;

    protected $table = 'annee_academiques';

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'est_active',
        'est_cloturee',
        'date_cloture',
        'observations',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_cloture' => 'date',
        'est_active' => 'boolean',
        'est_cloturee' => 'boolean',
    ];

    public function periodesPaie()
    {
        return $this->hasMany(PeriodePaie::class, 'annee_academique_id');
    }
}
