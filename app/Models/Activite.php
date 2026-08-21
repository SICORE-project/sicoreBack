<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    protected $table = 'activites';

    protected $fillable = ['code', 'libelle'];

    public function ventilations()
    {
        return $this->hasMany(VentilationDelegation::class);
    }
}
