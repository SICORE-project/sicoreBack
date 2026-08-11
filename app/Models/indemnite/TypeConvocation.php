<?php

namespace App\Models\Indemnites;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeConvocation extends Model
{
    protected $table = 'types_convocation';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function convocations(): HasMany
    {
        return $this->hasMany(\App\Models\Convocations::class, 'type_convocation_id');
    }
}
