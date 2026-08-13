<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convocations extends Model
{
    protected $table = 'convocations';

    protected $fillable = [
        'type_convocation_id',
        'date_emission',
        'date_debut',
        'date_fin',
        'heure_debut',
        'objet',
        'session',
        'lieu_examen',
        'ordre_de_mission',
        'lieu_affectation',
        'fichier_chemin',
        'statut',
        'utilisateur_id',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'ordre_de_mission' => 'boolean',
    ];

    public function typeConvocation(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Indemnite\TypeConvocation::class,
            'type_convocation_id'
        );
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Admin\User::class,
            'utilisateur_id'
        );
    }

    /**
     * Bénéficiaires affectés à la convocation.
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'convocation_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_id', 'centre_metier_id', 'provenance')->withTimestamps();
    }

    /**
     * Centres d'examen rattaches a cette convocation (cf. modele papier :
     * une convocation peut couvrir plusieurs centres, chacun avec son
     * propre jury/metier/chef de centre).
     */
    public function centres(): HasMany
    {
        return $this->hasMany(ConvocationCentre::class, 'convocation_id');
    }

    public function envois(): HasMany
    {
        return $this->hasMany(
            ConvocationEnvoi::class,
            'convocation_id'
        );
    }
}
