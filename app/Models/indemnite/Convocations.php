<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convocations extends Model
{
    protected $table = 'convocations';

    protected $fillable = [
        'date_emission',
        'objet',
        'lieu_examen',
        'ordre_de_mission',
        'lieu_affectation',
        'fichier_chemin',
        'statut',
        'utilisateur_id',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'ordre_de_mission' => 'boolean',
    ];

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
            \App\Models\Personnel\Enseignant::class,
            'convocation_enseignant',
            'convocation_id',
            'enseignant_id'
        )->withTimestamps();
    }

    public function envois(): HasMany
    {
        return $this->hasMany(
            ConvocationEnvoi::class,
            'convocation_id'
        );
    }
}
