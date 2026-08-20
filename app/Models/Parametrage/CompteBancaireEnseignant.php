<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteBancaireEnseignant extends Model
{
    use HasFactory;

    protected $table = 'comptes_bancaires_enseignants';

    protected $fillable = [
        'enseignant_id',
        'institut_financier_id',
        'numero_compte',
        'rib',
        'est_actif',
    ];

    protected $casts = ['est_actif' => 'boolean'];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function institutionFinanciere()
    {
        return $this->belongsTo(InstitutFinancier::class, 'institut_financier_id');
    }
}
