<?php

namespace App\Models\Parametrage;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Syndicat extends Model
{
    use HasFactory;

    protected $table = 'syndicats';

    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'adresse',
        'telephone',
        'email',
        'site_web',
        'responsable',
        'responsable_titre',
        'taux_cotisation',
        'est_actif',
    ];

    protected $casts = [
        'taux_cotisation' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    /**
     * Enseignants adhérents au syndicat.
     */
    public function enseignants()
    {
        return $this->belongsToMany(
            Enseignant::class,
            'enseignant_syndicat'
        )
        ->withPivot([
            'taux_personnalise',
            'date_adhesion',
            'date_resiliation',
            'numero_affiliation',
            'est_actif',
        ])
        ->withTimestamps();
    }
}