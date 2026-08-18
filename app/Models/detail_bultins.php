<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class detail_bultins extends Model
{
    protected $fillable = [
        'montant_gains', 'montant_retenus', 'bultin_id', 'rubrique_bultin_id',
    ];

    protected function casts(): array
    {
        return [
            'montant_gains' => 'decimal:2',
            'montant_retenus' => 'decimal:2',
        ];
    }

    public function bultin()
    {
        return $this->belongsTo(bultins::class, 'bultin_id');
    }

    public function rubrique()
    {
        return $this->belongsTo(rubrique_bultins::class, 'rubrique_bultin_id');
    }
}
