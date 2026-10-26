<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class etat_paie_indemnites extends Model
{
    protected $table = 'etat_paie_indemnites';

    protected $guarded = [];

    protected $casts = [
        'perimetre' => 'array',
        'details' => 'array',
        'date_generation' => 'date:Y-m-d',
        'periode_debut' => 'date:Y-m-d',
        'periode_fin' => 'date:Y-m-d',
        'total_montant' => 'float',
        'transmit_sica' => 'boolean',
        'verrouille' => 'boolean',
    ];
}
