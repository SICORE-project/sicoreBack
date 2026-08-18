<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatutEnseignant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'categorie',
        'description',
        'est_disponible_liste',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'est_disponible_liste' => 'boolean',
    ];

    // === RELATIONS ===
    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeDisponibleListe($query)
    {
        return $query->where('est_disponible_liste', true);
    }

    public function scopeByCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}