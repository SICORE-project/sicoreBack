<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LigneFraisDeplacement extends Model
{
    protected $table = 'lignes_frais_deplacement';
    protected $fillable = ['mission_id', 'type_frais', 'bareme_id', 'quantite', 'taux_unitaire', 'montant_declare', 'montant_calcule', 'montant_approuve', 'plafond_applique', 'justificatif_obligatoire', 'description'];
    protected $casts = ['quantite' => 'decimal:2', 'taux_unitaire' => 'decimal:2', 'montant_declare' => 'decimal:2', 'montant_calcule' => 'decimal:2', 'montant_approuve' => 'decimal:2', 'plafond_applique' => 'decimal:2', 'justificatif_obligatoire' => 'boolean'];
    public function mission(): BelongsTo { return $this->belongsTo(MissionDeplacement::class, 'mission_id'); }
    public function bareme(): BelongsTo { return $this->belongsTo(BaremeDeplacement::class, 'bareme_id'); }
    public function justificatifs(): HasMany { return $this->hasMany(JustificatifFraisDeplacement::class, 'ligne_frais_id'); }
}
