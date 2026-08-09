<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionDeplacement extends Model
{
    protected $table = 'missions_deplacement';
    protected $fillable = ['reference', 'beneficiaire_id', 'declare_par', 'lieu_depart', 'lieu_destination', 'motif', 'date_depart', 'date_retour', 'distance_km', 'moyen_transport', 'statut_agent', 'indice_agent', 'salaire_global_annuel', 'lieu_service', 'statut', 'montant_calcule', 'montant_approuve', 'valide_par', 'valide_at', 'motif_rejet', 'echeance_paiement', 'rembourse_le', 'rembourse_par', 'relance_at', 'notification_at', 'notification_message'];
    protected $casts = ['date_depart' => 'date', 'date_retour' => 'date', 'distance_km' => 'decimal:2', 'montant_calcule' => 'decimal:2', 'montant_approuve' => 'decimal:2', 'valide_at' => 'datetime', 'echeance_paiement' => 'date', 'rembourse_le' => 'datetime', 'relance_at' => 'datetime', 'notification_at' => 'datetime'];
    public function beneficiaire(): BelongsTo { return $this->belongsTo(\App\Models\Admin\User::class, 'beneficiaire_id'); }
    public function declarant(): BelongsTo { return $this->belongsTo(\App\Models\Admin\User::class, 'declare_par'); }
    public function lignes(): HasMany { return $this->hasMany(LigneFraisDeplacement::class, 'mission_id'); }
    public function justificatifs(): HasMany { return $this->hasMany(JustificatifFraisDeplacement::class, 'mission_id'); }
}
