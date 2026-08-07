<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaremeDeplacement extends Model
{
    protected $table = 'baremes_deplacement';
    protected $fillable = ['libelle', 'type_frais', 'zone', 'moyen_transport', 'taux_unitaire', 'plafond', 'justificatif_obligatoire', 'date_effet', 'date_fin', 'actif', 'cree_par'];
    protected $casts = ['taux_unitaire' => 'decimal:2', 'plafond' => 'decimal:2', 'justificatif_obligatoire' => 'boolean', 'actif' => 'boolean', 'date_effet' => 'date', 'date_fin' => 'date'];
}
