<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Paie\AnneeAcademique;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DelegationCredit extends Model
{
    protected $table = 'delegations_credits';

    protected $fillable = [
        'annee_academique_id',
        'reference_lettre',
        'objet',
       
        'periode_paie',
        'date_delegation',
    ];

    protected $casts = [
        'date_delegation' => 'date',
        'montant_total' => 'decimal:2',
    ];
    public function ventilations(): HasMany
    {
        return $this->hasMany(
            VentilationCredit::class,
            'delegation_credit_id'
        );
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(
            AnneeAcademique::class,
            'annee_academique_id'
        );
    }

}
