<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne de frais (transport / hébergement / restauration...) d'une
 * mission de déplacement — utilisé par l'étape "Calcul" (à venir), pas par
 * la simple création de la fiche de déplacement elle-même.
 */
class LigneFraisDeplacement extends Model
{
    protected $table = 'lignes_frais_deplacement';

    protected $fillable = [
        'mission_id',
        'bareme_id',
        'type_frais',
        'quantite',
        'taux_unitaire',
        'montant_declare',
        'montant_calcule',
        'montant_approuve',
        'plafond_applique',
        'justificatif_obligatoire',
        'description',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'taux_unitaire' => 'decimal:2',
        'montant_declare' => 'decimal:2',
        'montant_calcule' => 'decimal:2',
        'montant_approuve' => 'decimal:2',
        'plafond_applique' => 'decimal:2',
        'justificatif_obligatoire' => 'boolean',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionDeplacement::class, 'mission_id');
    }

    public function bareme(): BelongsTo
    {
        return $this->belongsTo(BaremeDeplacement::class, 'bareme_id');
    }
}
