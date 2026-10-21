<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diplome extends Model
{
    use HasFactory;

    protected $table = 'diplomes';

    protected $fillable = [
        'libelle',
        'categorie_id',
        'salaire_brut',
    ];

    protected $casts = [
        'salaire_brut' => 'decimal:2',
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }
}
