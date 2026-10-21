<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrage\Corps;

class RubriqueParCorps extends Model
{
    use HasFactory;

    protected $table = 'rubrique_par_corps';

    protected $fillable = [
        'corps_id',
        'rubrique_paie_id',
        'taux_personnalise',
        'montant_personnalise',
        'est_applicable',
        'formule_personnalisee',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'est_applicable' => 'boolean',
        'taux_personnalise' => 'decimal:2',
        'montant_personnalise' => 'decimal:2',
    ];

    // === RELATIONS ===
    public function corps()
    {
        return $this->belongsTo(Corps::class);
    }

    public function rubriquePaie()
    {
        return $this->belongsTo(RubriquePaie::class);
    }
}