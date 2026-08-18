<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class etablissements extends Model
{
    protected $fillable = ['libelle', 'niveau', 'code', 'email', 'telephone', 'adresse', 'ief_id'];

    public function ief()
    {
        return $this->belongsTo(iefs::class, 'ief_id');
    }
}
