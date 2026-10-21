<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndemniteSurveillance extends Model
{
    protected $table = 'indemnites_surveillance';

    protected $fillable = [
        'convocation_id',
        'convocation_centre_id',
        'enseignant_id',
        'metier',
        'nombre_heures',
        'tarif_horaire',
        'montant',
        'statut',
    ];

    protected $casts = [
        'nombre_heures' => 'decimal:2',
        'tarif_horaire' => 'decimal:2',
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
