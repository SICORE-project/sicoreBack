<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class indemnites extends Model
{
    protected $fillable = [
        'montant', 'montant_base', 'frais_deplacement', 'montant_total', 'statut',
        'nombre_copies', 'ordre_de_mission', 'lieu_affectation', 'indice',
        'nombre_heures', 'nombre_kilometrages', 'utilisateur_id', 'type_indemnite_id',
        'bareme_id', 'valide_par', 'valide_at', 'commentaire_validation',
        'etat_paie_indemnite_id', // CORRECTIF : trace l'état de paie qui a "consommé" cette indemnité
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_base' => 'decimal:2',
        'frais_deplacement' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'valide_at' => 'datetime',
    ];

    /**
     * État de paie dans lequel cette indemnité a été intégrée (si déjà généré).
     * Tant que null, l'indemnité est éligible à une future génération.
     */
    public function etatPaie(): BelongsTo
    {
        return $this->belongsTo(etat_paie_indemnites::class, 'etat_paie_indemnite_id');
    }
}
