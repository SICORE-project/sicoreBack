<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Type d'indemnité / barème utilisé pour le calcul automatique des indemnités.
 *
 * NOTE (complété par Claude, dans le périmètre "Indemnites"): ce modèle était
 * un stub vide sans $fillable, ce qui empêchait toute création/mise à jour
 * en mass assignment. Les colonnes ci-dessous sont déduites du besoin métier
 * (calcul forfaitaire / horaire / kilométrique) exprimé par les routes
 * indemnites/calculer et indemnites/simuler. A adapter/valider avec l'équipe
 * si le nom des colonnes diffère dans la migration réelle.
 */
class type_indemnites extends Model
{
    protected $table = 'type_indemnites';

    protected $fillable = [
        'libelle',
        'description',
        'mode_calcul',
        'montant_forfaitaire',
        'taux_horaire',
        'taux_kilometrique',
        'plafond',
        'actif',
    ];

    protected $casts = [
        'montant_forfaitaire' => 'decimal:2',
        'taux_horaire' => 'decimal:2',
        'taux_kilometrique' => 'decimal:2',
        'plafond' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function indemnites(): HasMany
    {
        return $this->hasMany(indemnites::class, 'type_indemnite_id');
    }
}
