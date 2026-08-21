<?php

namespace App\Models\Parametrage;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutuelle extends Model
{
    use HasFactory;

    protected $table = 'mutuelles';

    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'telephone',
        'email',
        'adresse',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    /**
     * Enseignants adhérents à la mutuelle.
     */
    public function enseignants()
    {
        return $this->belongsToMany(
            Enseignant::class,
            'enseignant_mutuelle'
        )
        ->withPivot([
            'numero_affiliation',
            'date_adhesion',
            'date_resiliation',
            'est_actif',
        ])
        ->withTimestamps();
    }
}