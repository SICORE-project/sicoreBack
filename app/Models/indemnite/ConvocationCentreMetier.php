<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un métier/spécialité couvert par un centre d'examen (un centre peut en
 * couvrir plusieurs — cf. modèle papier "convocation jury"). Les membres
 * du jury sont rattachés à un métier précis via
 * convocation_enseignant.centre_metier_id.
 */
class ConvocationCentreMetier extends Model
{
    protected $table = 'convocation_centre_metiers';

    protected $fillable = [
        'convocation_centre_id',
        'metier',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ConvocationCentre::class, 'convocation_centre_id');
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_metier_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_id', 'provenance', 'categorie_personnel')->withTimestamps();
    }
}
