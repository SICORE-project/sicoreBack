<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diplome extends Model
{
    use HasFactory;

    protected $table = 'diplomes';

    protected $fillable = [
        'code',
        'libelle',
        'type',
        'date_obteention',
    ];

    protected $casts = [
        'date_obteention' => 'date',
    ];
}
