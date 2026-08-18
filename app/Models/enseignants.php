<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class enseignants extends Model
{
    protected $fillable = [
        'indice', 'date_recrutement', 'situation_matrimoniale',
        'specialite', 'diplome', 'corps_enseignant_id',
        'specialite_id', 'diplome_id', 'mutuelle_id', 'institution_financiere_id',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'enseignant_id');
    }

    public function bultins()
    {
        return $this->hasMany(bultins::class, 'enseignant_id');
    }

    public function corpsEnseignant()
    {
        return $this->belongsTo(corps_enseignants::class, 'corps_enseignant_id');
    }
}
