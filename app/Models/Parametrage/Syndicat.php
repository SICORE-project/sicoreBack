<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Syndicat extends Model
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $table = 'syndicats';

    protected $fillable = [
        'code',
        'libelle',
        'montant_check_off',
        'montant_oeuvre_sociale',
        'est_actif',
    ];

// Définir les casts des montants et de l’état actif.
    protected $casts = [
        'montant_check_off' => 'decimal:2',
        'montant_oeuvre_sociale' => 'decimal:2',
        'est_actif' => 'boolean',
    ];
    
    // public function enseignants()
    // {
    //     return $this->hasMany(Enseignant::class, 'syndicat_id');
    // }

}
