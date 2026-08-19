<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Admin\User;

class Ief extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'ia_id',
        'adresse',
        'telephone',
        'email',
        'responsable',
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

    public function scopeByIa($query, $iaId)
    {
        return $query->where('ia_id', $iaId);
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->code . ' - ' . $this->libelle;
    }
}