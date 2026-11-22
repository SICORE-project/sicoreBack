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
        
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_cloture' => 'date',
        'est_active' => 'boolean',
        'est_cloturee' => 'boolean',
    ];

    /** Périodes de paie datées rattachées à cette année (payroll_periods.annee_academique_id). */
    public function payrollPeriods()
    {
        return $this->hasMany(\App\Models\PayrollPeriod::class, 'annee_academique_id');
    }
}
