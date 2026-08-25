<?php

namespace App\Models\Parametrage;

use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'libelle', 'description', 'statut'];

    public function enseignants()
    {
        return $this->hasMany(Enseignant::class, 'discipline_id');
    }
}
