<?php

namespace App\Models\Personnel;

use App\Models\Parametrage\InstitutFinancier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteBancaireEnseignant extends Model
{
    use HasFactory;

    protected $table = 'comptes_bancaires_enseignants';

    protected $fillable = [
        'enseignant_id',
        'code_banque',
        'code_guichet',
        'numero_compte',
        'cle_rib',
        'iban',
        'bic',
        'titulaire_compte',
        'type_virement',
        'institut_financier_id',
        'est_principal',
        'est_actif',
    ];

    protected $casts = [
        'est_principal' => 'boolean',
        'est_actif' => 'boolean',
    ];

    /**
     * Enseignant propriétaire du compte.
     */
    public function enseignant()
    {
        return $this->belongsTo(
            Enseignant::class,
            'enseignant_id'
        );
    }

    /**
     * Banque / institut financier.
     */
    public function institutFinancier()
    {
        return $this->belongsTo(
            InstitutFinancier::class,
            'institut_financier_id'
        );
    }
}