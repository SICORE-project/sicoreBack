<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'ordre',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'ordre' => 'integer',
    ];

    // === RELATIONS ===
    public function corps()
    {
        return $this->hasMany(Corps::class);
    }

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }
}