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

    public function getLibelleAttribute(): ?string
    {
        return $this->attributes['nom']
            ?? $this->attributes['libelle']
            ?? null;
    }

    // Vérifier si le rôle a une permission
    public function hasPermission($permissionSlug)
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    // Vérifier si le rôle a une permission par son ID
    public function hasPermissionId($permissionId)
    {
        return $this->permissions()->where('id', $permissionId)->exists();
    }

    // Donner une permission au rôle
    public function givePermission($permissionId)
    {
        if (!$this->hasPermissionId($permissionId)) {
            $this->permissions()->attach($permissionId);
        }
        return $this;
    }

    // Retirer une permission du rôle
    public function removePermission($permissionId)
    {
        if ($this->hasPermissionId($permissionId)) {
            $this->permissions()->detach($permissionId);
        }
        return $this;
    }

    // Synchroniser les permissions
    public function syncPermissions(array $permissionIds)
    {
        $this->permissions()->sync($permissionIds);
        return $this;
    }

    // Scope pour les rôles actifs
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    // Scope par niveau
    public function scopeNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }
}
