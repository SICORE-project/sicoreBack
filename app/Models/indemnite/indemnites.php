<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class indemnites extends Model
{
    protected $fillable = ['montant', 'montant_base', 'frais_deplacement', 'montant_total', 'statut', 'nombre_copies', 'ordre_de_mission', 'lieu_affectation', 'indice', 'nombre_heures', 'nombre_kilometrages', 'utilisateur_id', 'type_indemnite_id', 'bareme_id', 'valide_par', 'valide_at', 'commentaire_validation'];

    protected $casts = ['montant' => 'decimal:2', 'montant_base' => 'decimal:2', 'frais_deplacement' => 'decimal:2', 'montant_total' => 'decimal:2', 'valide_at' => 'datetime'];
}
