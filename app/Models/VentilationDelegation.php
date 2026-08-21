<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentilationDelegation extends Model
{
    protected $table = 'ventilations_delegation';

    public const TYPE_SALAIRE = 'salaire';
    public const TYPE_PRIME_SCOLAIRE = 'prime_scolaire';

    protected $fillable = [
        'delegation_credit_id',
        'corps_enseignant_id',
        'ia_id',
        'ief_id',
        'centre_execution_id',
        'budget_id',
        'activite_id',
        'imputation_budgetaire',
        'numero_autorisation',
        'numero_carton',
        'montant',
        'montant_engagement',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_engagement' => 'decimal:2',
        ];
    }

    public function delegationCredit()
    {
        return $this->belongsTo(DelegationCredit::class);
    }

    public function corpsEnseignant()
    {
        return $this->belongsTo(corps_enseignants::class, 'corps_enseignant_id');
    }

    public function ia()
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }

    public function ief()
    {
        return $this->belongsTo(iefs::class, 'ief_id');
    }

    public function centreExecution()
    {
        return $this->belongsTo(CentreExecution::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function activite()
    {
        return $this->belongsTo(Activite::class);
    }

    /** Reste a engager sur la ligne de ventilation. */
    public function getDisponibleAttribute(): string
    {
        return bcsub((string) $this->montant, (string) $this->montant_engagement, 2);
    }

    public function scopeSalaire($query)
    {
        return $query->where('type', self::TYPE_SALAIRE);
    }

    public function scopePrimeScolaire($query)
    {
        return $query->where('type', self::TYPE_PRIME_SCOLAIRE);
    }
}
