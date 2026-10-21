<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeRole extends Model
{
    use HasFactory;

    protected $table = 'type_roles';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function roles()
    {
        return $this->hasMany(Role::class, 'type_role_id');
    }
}
