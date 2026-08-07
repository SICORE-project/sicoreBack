<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConvocationEnvoi extends Model
{
    protected $table = 'convocation_envois';

    protected $fillable = [
        'convocation_id',
        'enseignant_id',
        'canal',
        'statut',
        'message',
        'date_envoi',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    /**
     * Convocation concernée par l'envoi.
     */
    public function convocation(): BelongsTo
    {
        return $this->belongsTo(
            Convocations::class,
            'convocation_id'
        );
    }

    /**
     * Bénéficiaire destinataire.
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(
            Enseignants::class,
            'enseignant_id'
        );
    }
}
