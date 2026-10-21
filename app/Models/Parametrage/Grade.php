<?php

namespace App\Models\Parametrage;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    public function enseignants()
    {
        return $this->hasMany(
            Enseignant::class,
            'grade_id'
        );
    }
}