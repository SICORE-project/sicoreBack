<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PermissionModule extends Model
{
    protected $table = 'permission_modules';

    protected $fillable = ['code', 'libelle', 'groupe_id', 'est_actif'];

    protected $casts = ['est_actif' => 'boolean'];

    protected $appends = ['nom'];

    public function getNomAttribute(): string
    {
        return $this->libelle;
    }

    public function groupe()
    {
        return $this->belongsTo(PermissionGroupe::class, 'groupe_id');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'module', 'code');
    }
}
