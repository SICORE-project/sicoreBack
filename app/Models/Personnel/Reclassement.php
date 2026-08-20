<?php

namespace App\Models\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;
use App\Models\Parametrage\Corps;
use App\Models\Parametrage\Grade;
use App\Models\Parametrage\Echelon;

class Reclassement extends Model
{
    use HasFactory;

    protected $fillable = [
        'enseignant_id',
        'ancien_corps_id',
        'ancien_grade_id',
        'ancien_echelon_id',
        'ancien_statut',
        'nouveau_corps_id',
        'nouveau_grade_id',
        'nouveau_echelon_id',
        'nouveau_statut',
        'date_reclassement',
        'motif',
        'observations',
        'numero_arrete',
        'date_arrete',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'date_reclassement' => 'date',
        'date_arrete' => 'date',
        'validated_at' => 'datetime',
    ];

    // === RELATIONS ===
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function ancienCorps()
    {
        return $this->belongsTo(Corps::class, 'ancien_corps_id');
    }

    public function ancienGrade()
    {
        return $this->belongsTo(Grade::class, 'ancien_grade_id');
    }

    public function ancienEchelon()
    {
        return $this->belongsTo(Echelon::class, 'ancien_echelon_id');
    }

    public function nouveauCorps()
    {
        return $this->belongsTo(Corps::class, 'nouveau_corps_id');
    }

    public function nouveauGrade()
    {
        return $this->belongsTo(Grade::class, 'nouveau_grade_id');
    }

    public function nouveauEchelon()
    {
        return $this->belongsTo(Echelon::class, 'nouveau_echelon_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // === SCOPES ===
    public function scopeByEnseignant($query, $enseignantId)
    {
        return $query->where('enseignant_id', $enseignantId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date_reclassement', $date);
    }
}