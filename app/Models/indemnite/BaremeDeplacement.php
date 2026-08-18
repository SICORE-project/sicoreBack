<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Taux de référence (transport / hébergement / restauration...) utilisé
 * par l'étape "Calcul" (à venir) pour valoriser les lignes de frais.
 */
class BaremeDeplacement extends Model
{
    protected $table = 'baremes_deplacement';

    protected $fillable = [
        'libelle',
        'type_frais',
        'zone',
        'moyen_transport',
        'taux_unitaire',
        'plafond',
        'justificatif_obligatoire',
        'date_effet',
        'date_fin',
        'actif',
        'cree_par',
    ];

    protected $casts = [
        'taux_unitaire' => 'decimal:2',
        'plafond' => 'decimal:2',
        'justificatif_obligatoire' => 'boolean',
        'date_effet' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFraisDeplacement::class, 'bareme_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'cree_par');
    }
}
