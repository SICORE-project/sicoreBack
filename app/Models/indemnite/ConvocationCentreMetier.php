<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un metier au sein d'UN centre d'examen (cf. modele papier "convocation
 * jury BT" : un centre a un jury et un chef de centre uniques, mais peut
 * couvrir plusieurs metiers, chacun avec ses propres membres du jury).
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

    /**
     * Membres du jury affectes a CE metier precis (cf.
     * convocation_enseignant.centre_metier_id).
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_metier_id',
            'enseignant_id'
        )->withPivot('fonction', 'provenance', 'centre_id')->withTimestamps();
    }
}
