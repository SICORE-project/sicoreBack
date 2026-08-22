<?php

namespace App\Models\Parametrage;

use App\Models\Admin\User;
use App\Models\Personnel\AffectationEnseignant;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LieuService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lieu_de_services';

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

    public function affectationsEnseignants()
    {
        return $this->hasMany(AffectationEnseignant::class);
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
        return $this->code.' - '.$this->libelle;
    }

    public function getHierarchieCoherenteAttribute(): bool
    {
        return $this->ief_id === null
            || ($this->ief !== null && (int) $this->ief->ia_id === (int) $this->ia_id);
    }
}
