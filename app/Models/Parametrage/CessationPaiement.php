<?php

namespace App\Models\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;

class CessationPaiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'enseignant_id',
        'type',
        'date_effet',
        'date_reprise',
        'motif',
        'observations',
        'est_definitif',
        'est_actif',
        'numero_arrete',
        'date_arrete',
        'created_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'est_definitif' => 'boolean',
        'est_actif' => 'boolean',
        'date_effet' => 'date',
        'date_reprise' => 'date',
        'date_arrete' => 'date',
        'validated_at' => 'datetime',
    ];

    // === RELATIONS ===
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
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
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeDefinitif($query)
    {
        return $query->where('est_definitif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}