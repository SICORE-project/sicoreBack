<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
        'fonction',
        'statut',
        'password',
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_naiss' => 'date',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'verrouille_jusqua' => 'datetime',
            'derniere_connexion' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(roles::class, 'role_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(enseignants::class, 'enseignant_id');
    }

    public function ief(): BelongsTo
    {
        return $this->belongsTo(iefs::class, 'ief_id');
    }

    public function ia(): BelongsTo
    {
        return $this->belongsTo(ias::class, 'ia_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(self::class, 'created_by');
    }

    public function updatedUsers(): HasMany
    {
        return $this->hasMany(self::class, 'updated_by');
    }
}
