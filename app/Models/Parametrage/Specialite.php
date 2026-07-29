<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialite extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'domaine',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }
}