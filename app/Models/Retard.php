<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retard extends Model
{
    protected $table = 'retards';

    protected $fillable = [
        'etat_presence_id',
        'enseignant_id',
        'date_retard',
        'duree_minutes',
        'motif',
        'justifie',
        'retenue_montant',
    ];

    protected $casts = [
        'date_retard' => 'date',
        'justifie' => 'boolean',
        'retenue_montant' => 'decimal:2',
    ];

    public function etatPresence()
    {
        return $this->belongsTo(EtatPresence::class, 'etat_presence_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(enseignants::class, 'enseignant_id');
    }
}
