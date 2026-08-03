<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'niveau',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class, 
            'role_permission',
            'role_id', 
            'permission_id'
            );
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    // === METHODS ===
    public function hasPermission($permissionSlug)
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function givePermission($permissionId)
    {
        return $this->permissions()->attach($permissionId);
    }

    public function removePermission($permissionId)
    {
        return $this->permissions()->detach($permissionId);
    }

    public function syncPermissions(array $permissionIds)
    {
        return $this->permissions()->sync($permissionIds);
    }
}