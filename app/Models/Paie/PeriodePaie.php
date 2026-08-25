<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodePaie extends Model
{
    use HasFactory;

    protected $table = 'periode_de_paies';

    protected $fillable = [
        'code',
        'libelle',
    ];
}
