<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class rubrique_bultins extends Model
{
    protected $fillable = ['libelle', 'type'];

    public function details()
    {
        return $this->hasMany(detail_bultins::class, 'rubrique_bultin_id');
    }
}
