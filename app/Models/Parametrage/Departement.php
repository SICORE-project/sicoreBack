<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'region_id',
        'chef_lieu',
        'population',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'population' => 'integer',
    ];

    // === RELATIONS ===
    public function region()
    {
        return $this->belongsTo(Region::class);
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

    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}