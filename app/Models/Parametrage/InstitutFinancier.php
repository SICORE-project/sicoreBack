<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Personnel\Enseignant;
use App\Models\Personnel\CompteBancaireEnseignant;

class InstitutFinancier extends Model
{
    use HasFactory;

    protected $table = 'instituts_financieres';

    protected $fillable = [
        'code',
        'libelle',
        'adresse',
        'telephone',
        'email',
        'code_banque',
        'code_guichet',
        'iban_exemple',
    ];

    // === RELATIONS ===

    public function comptesBancairesEnseignants()
    {
        return $this->hasMany(
            CompteBancaireEnseignant::class,
            'institut_financier_id'
        );
    }

    public function enseignants()
    {
        return $this->belongsToMany(
            Enseignant::class,
            'enseignant_institut_financier'
        )
        ->withPivot(
            'iban',
            'bic',
            'titulaire_compte',
            'est_principal',
            'est_actif'
        )
        ->withTimestamps();
    }
}