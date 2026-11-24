<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentilationCredit extends Model
{
    protected $table = 'ventilations_credits';

    protected $fillable = [
        'delegation_credit_id',
        'corps_enseignant_id',
        'ia_id',
        'ief_id',
        'centre_execution',
        'budget',
        'activite',
        'imputation_budgetaire',
        'numero_autorisation',
        'numero_carton',
        'engagement',
        'type_ventilation',
        'montant',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(
            DelegationCredit::class,
            'delegation_credit_id'
        );
    }
}