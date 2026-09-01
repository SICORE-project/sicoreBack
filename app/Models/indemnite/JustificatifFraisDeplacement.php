<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fichier déposé pour une mission de déplacement — sert notamment à
 * rattacher la feuille de déplacement scannée/remplie (photo/PDF) à sa
 * fiche numérique.
 */
class JustificatifFraisDeplacement extends Model
{
    protected $table = 'justificatifs_frais_deplacement';

    protected $fillable = [
        'mission_id',
        'ligne_frais_id',
        'nom_original',
        'chemin',
        'mime_type',
        'taille',
        'depose_par',
        'conforme',
        'verifie_par',
        'verifie_at',
        'commentaire',
    ];

    protected $casts = [
        'taille' => 'integer',
        'conforme' => 'boolean',
        'verifie_at' => 'datetime',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionDeplacement::class, 'mission_id');
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(LigneFraisDeplacement::class, 'ligne_frais_id');
    }

    public function deposant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'depose_par');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'verifie_par');
    }
}
