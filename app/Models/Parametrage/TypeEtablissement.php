<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEtablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'categorie',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function centresFormation()
    {
        return $this->hasMany(CentreFormation::class);
    }
}