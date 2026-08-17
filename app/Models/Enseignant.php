<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enseignant extends Model
{
    protected $table = 'enseignants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_recrutement' => 'date',
            'salaire_base' => 'decimal:2',
            'impr_monthly_amount' => 'decimal:2',
            'trimf_monthly_amount' => 'decimal:2',
            'ipm_monthly_amount' => 'decimal:2',
            'union_checkoff_monthly_amount' => 'decimal:2',
            'actif' => 'boolean',
            'imported_at' => 'datetime',
            'payroll_profile_configured_at' => 'datetime',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'enseignant_id');
    }

    public function institutionFinanciere(): BelongsTo
    {
        return $this->belongsTo(institution_financieres::class, 'institution_financiere_id');
    }

    public function corps(): BelongsTo
    {
        return $this->belongsTo(corps_enseignants::class, 'corps_enseignant_id');
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(etablissements::class, 'etablissement_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(PayrollAttendance::class);
    }

    public function payrollElements(): HasMany
    {
        return $this->hasMany(PayrollElement::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayrollPayslip::class);
    }
}
