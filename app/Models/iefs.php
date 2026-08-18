<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class iefs extends Model
{
    protected $fillable = ['libelle', 'code', 'telephone', 'adresse', 'email', 'departement', 'ia_id'];

    public function ia()
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }

    public function etablissements()
    {
        return $this->hasMany(etablissements::class, 'ief_id');
    }
}
