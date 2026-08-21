<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = 'budgets';

    protected $fillable = ['code', 'libelle'];

    public function ventilations()
    {
        return $this->hasMany(VentilationDelegation::class);
    }
}
