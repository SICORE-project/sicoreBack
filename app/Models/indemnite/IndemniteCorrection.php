<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Indemnité de correction d'un membre (fonction "Correction") pour UN
 * métier d'UN centre d'une convocation — montant = nombre_copies x
 * taux_copie, taux saisi librement à chaque calcul (pas de barème). Voir
 * IndemniteCorrectionController::calculerGroupe().
 */
class IndemniteCorrection extends Model
{
    protected $table = 'indemnites_correction';

    protected $fillable = [
        'convocation_id',
        'convocation_centre_id',
        'enseignant_id',
        'metier',
        'nombre_copies',
        'taux_copie',
        'montant',
        'statut',
    ];

    protected $casts = [
        'nombre_copies' => 'integer',
        'taux_copie' => 'decimal:2',
        'montant' => 'decimal:2',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class, 'convocation_id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ConvocationCentre::class, 'convocation_centre_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class, 'enseignant_id');
    }
}
