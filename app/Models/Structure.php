<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $fillable = ['nom', 'description'];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function delegationCredits()
    {
        return $this->hasMany(DelegationCredit::class);
    }
}
