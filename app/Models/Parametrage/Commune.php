<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'libelle',
        'region_id',
        'departement_id',
        'est_actif',
    ];

    protected $casts = [
        'region_id' => 'integer',
        'departement_id' => 'integer',
        'est_actif' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    public function scopeByDepartement($query, $departementId)
    {
        return $query->where('departement_id', $departementId);
    }
}