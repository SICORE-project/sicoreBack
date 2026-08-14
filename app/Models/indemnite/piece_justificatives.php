<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Piece_justificatives extends Model
{
    protected $table = 'piece_justificatives';

    protected $fillable = [
        'type', 'document_url', 'date_depot', 'statut', 'dossier_complet',
        'convocation_id', 'nom_original', 'chemin', 'mime_type', 'taille',
        'depositaire_id', 'verifie_par', 'verifie_at', 'conforme',
        'commentaire_verification', 'valide_par', 'valide_at',
        'commentaire_rejet', 'notification_at', 'notification_message',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'dossier_complet' => 'boolean',
        'conforme' => 'boolean',
        'verifie_at' => 'datetime',
        'valide_at' => 'datetime',
        'notification_at' => 'datetime',
        'taille' => 'integer',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class);
    }

    public function depositaire(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'depositaire_id');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'verifie_par');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'valide_par');
    }
}
