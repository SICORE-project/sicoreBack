<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceFaitHistorique extends Model
{
    protected $table = 'services_faits_historiques';

    protected $fillable = [
        'service_fait_id',
        'utilisateur_id',
        'action',
        'anciennes_valeurs',
        'nouvelles_valeurs',
    ];

    protected $casts = [
        'anciennes_valeurs' => 'array',
        'nouvelles_valeurs' => 'array',
    ];

    public function serviceFait(): BelongsTo
    {
        return $this->belongsTo(
            ServiceFait::class,
            'service_fait_id'
        );
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Admin\User::class,
            'utilisateur_id'
        );
    }
}
