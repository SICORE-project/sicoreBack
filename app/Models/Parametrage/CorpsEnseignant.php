<?php

namespace App\Models\Parametrage;

use App\Models\Paie\RubriquePaie;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorpsEnseignant extends Model
{
    use HasFactory;

    protected $table = 'corps_enseignant';

    protected $fillable = [
        'code',
        'libelle',
        'sigle',
        'categorie_id',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function categorie()
    {
        return $this->belongsTo(
            Categorie::class,
            'categorie_id'
        );
    }

    public function enseignants()
    {
        return $this->hasMany(
            Enseignant::class,
            'corps_id'
        );
    }

    public function rubriques()
    {
        return $this->belongsToMany(
            RubriquePaie::class,
            'rubrique_par_corps'
        )
        ->withPivot([
            'taux_personnalise',
            'montant_personnalise',
            'est_applicable',
            'formule_personnalisee',
            'est_actif',
        ])
        ->withTimestamps();
    }
}