<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConvocationCentre extends Model
{
    protected $table = 'convocation_centres';

    protected $fillable = [
        'convocation_id',
        'centre',
        'jury',
        'metier',
        'chef_centre_id',
        'chef_centre_telephone',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class, 'convocation_id');
    }

    public function chefCentre(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class, 'chef_centre_id');
    }

    /**
     * Membres du jury affectes a CE centre precis, au sein de la
     * convocation (voir la colonne centre_id sur convocation_enseignant).
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_id',
            'enseignant_id'
        )->withPivot('fonction', 'provenance')->withTimestamps();
    }
}