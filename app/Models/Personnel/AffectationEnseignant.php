<?php

namespace App\Models\Personnel;

use App\Models\Admin\User;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\LieuService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffectationEnseignant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'affectations_enseignants';

    protected $fillable = [
        'enseignant_id',
        'ia_id',
        'ief_id',
        'lieu_service_id',
        'centre_formation_id',
        'date_debut',
        'date_fin',
        'type',
        'motif',
        'observations',
        'est_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_active' => 'boolean',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function lieuService()
    {
        return $this->belongsTo(LieuService::class);
    }

    public function ia()
    {
        return $this->belongsTo(Ia::class);
    }

    public function ief()
    {
        return $this->belongsTo(Ief::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
