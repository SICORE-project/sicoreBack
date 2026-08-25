<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;

class Ia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'region_id',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function iefs()
    {
        return $this->hasMany(Ief::class);
    }

    public function lieuxServices()
    {
        return $this->hasMany(LieuService::class);
    }

    public function centresFormation()
    {
        return $this->hasMany(CentreFormation::class);
    }

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region_id', $region);
    }

    public function scopeByDepartement($query, $departement)
    {
        return $query->where('departement_id', $departement);
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->code . ' - ' . $this->libelle;
    }
}
