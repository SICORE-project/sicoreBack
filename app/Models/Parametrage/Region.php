<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'chef_lieu',
        'superficie',
        'population',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'superficie' => 'decimal:2',
        'population' => 'integer',
    ];

    // === RELATIONS ===
    public function departements()
    {
        return $this->hasMany(Departement::class);
    }

    public function centresFormation()
    {
        return $this->hasMany(CentreFormation::class);
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }
}