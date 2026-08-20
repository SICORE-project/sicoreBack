<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'code',
        'libelle',
        'ordre',
        'description',
        'corps_id',
        'est_actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'est_actif' => 'boolean',
    ];

    public function corps()
    {
        return $this->belongsTo(
            CorpsEnseignant::class,
            'corps_id'
        );
    }
}