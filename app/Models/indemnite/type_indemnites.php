<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Type_indemnites extends Model
{
    protected $table = 'type_indemnites';

    protected $fillable = [
        'libelle',
        'description',
        'mode_calcul',
        'montant_forfaitaire',
        'taux_horaire',
        'taux_kilometrique',
        'plafond',
        'actif',
    ];

    protected $casts = [
        'montant_forfaitaire' => 'decimal:2',
        'taux_horaire' => 'decimal:2',
        'taux_kilometrique' => 'decimal:2',
        'plafond' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function indemnites(): HasMany
    {
        return $this->hasMany(indemnites::class, 'type_indemnite_id');
    }
}
