<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etat_paie_indemnites extends Model
{
    protected $table = 'etat_paie_indemnites';

    protected $fillable = ['reference', 'type', 'beneficiaire_id', 'utilisateur_id', 'date_generation', 'periode_debut', 'periode_fin', 'perimetre', 'details', 'total_montant', 'lieu_examen', 'session', 'transmit_sica', 'statut', 'verrouille', 'valide_par', 'valide_at', 'commentaire_correction', 'archive_par', 'archive_at'];

    protected $casts = ['date_generation' => 'date', 'periode_debut' => 'date', 'periode_fin' => 'date', 'perimetre' => 'array', 'details' => 'array', 'total_montant' => 'decimal:2', 'transmit_sica' => 'boolean', 'verrouille' => 'boolean', 'valide_at' => 'datetime', 'archive_at' => 'datetime'];

    public function beneficiaire(): BelongsTo { return $this->belongsTo(\App\Models\Admin\User::class, 'beneficiaire_id'); }
    public function generateur(): BelongsTo { return $this->belongsTo(\App\Models\Admin\User::class, 'utilisateur_id'); }
    public function validateur(): BelongsTo { return $this->belongsTo(\App\Models\Admin\User::class, 'valide_par'); }
}
