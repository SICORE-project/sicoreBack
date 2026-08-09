<?php

namespace App\Models\indemnites;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
     use HasFactory;

    protected $table = 'agents';

    protected $fillable = [
        'user_id',
        'matricule',
        'lieu_de_services_id',
    ];

    protected $hidden = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lieuDeService(): BelongsTo
    {
        return $this->belongsTo(LieuDeService::class);
    }

    public function accusesDeposes(): HasMany
    {
        return $this->hasMany(
            Accuse_reception::class,
            'agent_deposant_id'
        );
    }

    public function accusesReceptionnes(): HasMany
    {
        return $this->hasMany(
            Accuse_reception::class,
            'agent_receptionnaire_id'
        );
    }

    public function getNomCompletAttribute(): string
    {
        return trim(
            "{$this->user?->prenom} {$this->user?->nom}"
        );
    }
}
