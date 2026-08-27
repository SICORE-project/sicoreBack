<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PermissionGroupe extends Model
{
    protected $table = 'permission_groupes';

    protected $fillable = ['code', 'libelle', 'est_actif'];

    protected $casts = ['est_actif' => 'boolean'];

    public function modules()
    {
        return $this->hasMany(PermissionModule::class, 'groupe_id');
    }
}
