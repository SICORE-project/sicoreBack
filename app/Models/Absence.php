<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    protected $table = 'absences';

    protected $fillable = [
        'etat_presence_id',
        'enseignant_id',
        'date_absence',
        'motif',
        'justifiee',
        'piece_justificative',
        'date_justification',
        'retenue_montant',
    ];

    protected $casts = [
        'date_absence' => 'date',
        'date_justification' => 'date',
        'justifiee' => 'boolean',
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
