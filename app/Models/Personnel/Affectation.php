<?php

namespace App\Models\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\CentreFormation;

class Affectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'enseignant_id',
        'ancien_lieu_service_id',
        'ancien_ief_id',
        'ancien_ia_id',
        'ancien_centre_formation_id',
        'nouveau_lieu_service_id',
        'nouveau_ief_id',
        'nouveau_ia_id',
        'nouveau_centre_formation_id',
        'type',
        'date_effet',
        'date_fin',
        'motif',
        'observations',
        'numero_arrete',
        'date_arrete',
        'statut',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'date_effet' => 'date',
        'date_fin' => 'date',
        'date_arrete' => 'date',
        'validated_at' => 'datetime',
    ];

    // === RELATIONS ===
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function ancienLieuService()
    {
        return $this->belongsTo(LieuService::class, 'ancien_lieu_service_id');
    }

    public function ancienIef()
    {
        return $this->belongsTo(Ief::class, 'ancien_ief_id');
    }

    public function ancienIa()
    {
        return $this->belongsTo(Ia::class, 'ancien_ia_id');
    }

    public function ancienCentreFormation()
    {
        return $this->belongsTo(CentreFormation::class, 'ancien_centre_formation_id');
    }

    public function nouveauLieuService()
    {
        return $this->belongsTo(LieuService::class, 'nouveau_lieu_service_id');
    }

    public function nouveauIef()
    {
        return $this->belongsTo(Ief::class, 'nouveau_ief_id');
    }

    public function nouveauIa()
    {
        return $this->belongsTo(Ia::class, 'nouveau_ia_id');
    }

    public function nouveauCentreFormation()
    {
        return $this->belongsTo(CentreFormation::class, 'nouveau_centre_formation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}