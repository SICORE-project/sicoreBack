<?php

namespace App\Models\Paie;

use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubriquePaie extends Model
{
    use HasFactory;

    protected $table = 'rubrique_paies';

    protected $fillable = [
        'code',
        'libelle',
        'type',
        'periodicite',
        'description',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'est_cotisable' => 'boolean',
        'est_imposable' => 'boolean',
        'est_afficher_bulletin' => 'boolean',
        'taux_defaut' => 'decimal:2',
        'montant_defaut' => 'decimal:2',
    ];

    // === RELATIONS ===
    public function corps()
    {
        return $this->belongsToMany(
            CorpsEnseignant::class,
            'rubrique_par_corps',
            'rubrique_paie_id',
            'corps_id',
        )
            ->withPivot('taux_personnalise', 'montant_personnalise', 'est_applicable', 'formule_personnalisee', 'est_actif')
            ->withTimestamps();
    }

    // === SCOPES ===
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeLibelleAttribute()
    {
        $types = ['gain' => 'Gain', 'retenue' => 'Retenue'];

        return $types[$this->type] ?? $this->type;
    }
}
