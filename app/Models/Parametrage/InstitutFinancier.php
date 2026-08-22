<?php

namespace App\Models\Parametrage;

use App\Models\Personnel\CompteBancaireEnseignant;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutFinancier extends Model
{
    use HasFactory;

    protected $table = 'instituts_financieres';

    protected $fillable = [
        'code',
        'libelle',
        'sigle',
        'type_institution',
        'adresse',
        'telephone',
        'email',
        'code_banque',
        'code_guichet',
        'iban_exemple',
    ];

    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

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
        return $this->belongsToMany(Enseignant::class, 'enseignant_institut_financier')
            ->withPivot('iban', 'bic', 'titulaire_compte', 'est_principal', 'est_actif')
            ->withTimestamps();
    }
}
