<?php

namespace App\Models\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;
use App\Models\Paie\PeriodePaie;

class MiseAJourMcVe extends Model
{
    use HasFactory;

    protected $table = 'mise_a_jour_mc_ve';

    protected $fillable = [
        'enseignant_id',
        'type',
        'libelle',
        'montant',
        'date_effet',
        'date_fin',
        'periode_paie_id',
        'statut',
        'motif',
        'observations',
        'numero_arrete',
        'date_arrete',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
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

    public function periodePaie()
    {
        return $this->belongsTo(PeriodePaie::class);
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
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeMc($query)
    {
        return $query->where('type', 'mc');
    }

    public function scopeVe($query)
    {
        return $query->where('type', 've');
    }
}