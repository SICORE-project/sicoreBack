<?php

namespace App\Models\Parametrage;

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
        'site_web',
        'code_banque',
        'code_guichet',
        'iban_exemple',
        'bic',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // === RELATIONS ===
    public function lieuxPaiement()
    {
        return $this->hasMany(LieuPaiement::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'enseignant_institut_financier')
            ->withPivot('iban', 'bic', 'titulaire_compte', 'est_principal', 'est_actif')
            ->withTimestamps();
    }
}
