<?php

namespace App\Models\Indemnite;

use App\Helpers\Indemnites\HasOpaqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un métier/spécialité couvert par un centre d'examen (un centre peut en
 * couvrir plusieurs — cf. modèle papier "convocation jury"). Les membres
 * du jury sont rattachés à un métier précis via
 * convocation_enseignant.centre_metier_id.
 */
class ConvocationCentreMetier extends Model
{
    use HasOpaqueSlug;

    protected $table = 'convocation_centre_metiers';

    protected $fillable = [
        'convocation_centre_id',
        'metier',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ConvocationCentre::class, 'convocation_centre_id');
    }

    /**
     * 'categorie_personnel' est une colonne du pivot
     * `convocation_enseignant` depuis la migration
     * 2026_08_15_100000_add_categorie_personnel_to_convocation_enseignant_table
     * (avant elle, l'ajouter ici cassait tout eager-load
     * "centres.metiers.enseignants" avec SQLSTATE 42S22, colonne
     * inexistante — d'où l'avertissement précédent). Sans elle dans
     * withPivot(), la colonne "Statut" de la fiche convocation restait vide
     * même quand la valeur était bien enregistrée en base — même bug
     * corrigé sur Convocations::enseignants() et ConvocationCentre::enseignants().
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_metier_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_id', 'provenance', 'categorie_personnel')->withTimestamps();
    }
}
