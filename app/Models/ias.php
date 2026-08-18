<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ias extends Model
{
    protected $fillable = ['libelle', 'code', 'centre_geo', 'gouvernance'];

    public function iefs()
    {
        return $this->hasMany(iefs::class, 'ia_id');
    }

    public function bultins()
    {
        return $this->hasMany(bultins::class, 'ia_id');
    }
}
