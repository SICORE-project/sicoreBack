<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['nom', 'structure_id'];

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }
}
