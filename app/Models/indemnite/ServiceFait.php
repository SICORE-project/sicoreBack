<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceFait extends Model
{
    protected $fillable = [
        'convocation_id',
        'enseignant_id',
        'utilisateur_id',
        'date_debut',
        'date_fin',
        'lieu',
        'description',
        'nombre_jours',
        'statut',
        'motif_rejet',
        'valide_par',
        'valide_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'valide_at' => 'datetime',
        'nombre_jours' => 'integer',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(
            Convocations::class,
            'convocation_id'
        );
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Personnel\Enseignant::class,
            'enseignant_id'
        );
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Admin\User::class,
            'utilisateur_id'
        );
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Admin\User::class,
            'valide_par'
        );
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(
            ServiceFaitHistorique::class,
            'service_fait_id'
        );
    }
}
