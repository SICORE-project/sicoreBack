<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentreExecution extends Model
{
    protected $table = 'centres_execution';

    protected $fillable = ['code', 'libelle'];

    public function ventilations()
    {
        return $this->hasMany(VentilationDelegation::class);
    }
}
