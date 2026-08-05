<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diplome extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'sigle',
        'niveau',
        'type',
        'duree_annees',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'niveau' => 'integer',
        'duree_annees' => 'integer',
    ];

    // === RELATIONS ===
    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }
}