<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'groupe',
        'module',
        'action',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id');
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByGroupe($query, $groupe)
    {
        return $query->where('groupe', $groupe);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }
}