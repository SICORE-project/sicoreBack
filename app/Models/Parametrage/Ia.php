<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;

class Ia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'region_id',
        'departement_id',
        'adresse',
        'telephone',
        'email',
        'responsable',
        'est_actif',

    ];

    protected $casts = [
        'region_id' => 'integer',
        'departement_id' => 'integer',
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===

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

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    // === SCOPES ===

    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    public function scopeByDepartement($query, $departementId)
    {
        return $query->where('departement_id', $departementId);
    }

    // === ACCESSORS ===

    public function getNomCompletAttribute()
    {
        return $this->code . ' - ' . $this->libelle;
    }
}
