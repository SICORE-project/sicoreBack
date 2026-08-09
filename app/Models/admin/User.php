<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use App\Models\admin\Role;
use App\Models\indemnites\Agent;
use App\Models\Parametrage\LieuService;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\Ia;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'nom',
        'prenom',
        'genre',
        'date_naiss',
        'lieu_naissance',
        'telephone',
        'adresse',
        'photo',
        'email',
        'email_verified_at',
        'fonction',
        'statut',
        'password',
        'remember_token',
        'must_change_password',
        'password_changed_at',
        'tentatives_connexion',
        'verrouille_jusqua',
        'derniere_connexion',
        'role_id',
        'enseignant_id',
        'lieu_service_id',
        'ief_id',
        'ia_id',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
    return [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'verrouille_jusqua' => 'datetime',
        'derniere_connexion' => 'datetime',
        'must_change_password' => 'boolean',
        'date_naiss' => 'date',
        'password' => 'hashed',
    ];
    }

    // === RELATIONS ===


    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function lieuService()
    {
        return $this->belongsTo(LieuService::class);
    }

    public function ief()
    {
        return $this->belongsTo(Ief::class);
    }

    public function ia()
    {
        return $this->belongsTo(Ia::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    // === ACCESSORS ===
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // === METHODS ===
    public function hasRole($roleName)
    {
        return $this->role && $this->role->slug === $roleName;
    }

    public function hasPermission($permissionSlug)
    {
        if (!$this->role) return false;
        return $this->role->permissions()->where('slug', $permissionSlug)->exists();
    }
    // === MÉTHODES DE PERMISSIONS ===

public function role()
{
    return $this->belongsTo(Role::class);
}


public function hasRoleId($roleId)
{
    return $this->role_id === $roleId;
}

public function hasAnyPermission(array $permissions)
{
    foreach ($permissions as $permission) {
        if ($this->hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

public function hasAllPermissions(array $permissions)
{
    foreach ($permissions as $permission) {
        if (!$this->hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

public function isAdmin()
{
    return $this->hasRole('admin');
}

public function isParametreur()
{
    return $this->hasRole('parametreur');
}

public function isGestionnaire()
{
    return $this->hasRole('gestionnaire_paie') || $this->hasRole('gestionnaire_budget');
}

public function agent(): HasOne
{
    return $this->hasOne(Agent::class);
}
}
