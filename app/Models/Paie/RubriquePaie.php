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
