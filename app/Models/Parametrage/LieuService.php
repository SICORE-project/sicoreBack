<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;

class LieuService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'region',
        'departement',
        'commune',
        'adresse',
        'telephone',
        'email',
        'responsable',
        'type',
        'perimetre',
        'ia_id',
        'ief_id',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function ia()
    {
        return $this->belongsTo(Ia::class);
    }

    public function ief()
    {
        return $this->belongsTo(Ief::class);
    }

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
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

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByIa($query, $iaId)
    {
        return $query->where('ia_id', $iaId);
    }

    public function scopeByIef($query, $iefId)
    {
        return $query->where('ief_id', $iefId);
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->code . ' - ' . $this->libelle;
    }
}