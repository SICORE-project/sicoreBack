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

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permission',
            'permission_id', 
            'role_id'
            );
    }

    // Scope pour les permissions actives
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    // Scope par groupe
    public function scopeGroupe($query, $groupe)
    {
        return $query->where('groupe', $groupe);
    }

    // Scope par module
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    // Scope par action
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }
}