<?php

namespace App\Models\Indemnite;

use App\Helpers\Indemnites\HasOpaqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConvocationCentre extends Model
{
    use HasOpaqueSlug;

    protected $table = 'convocation_centres';

    protected $fillable = [
        'convocation_id',
        'centre',
        'jury',
        'metier',
        'chef_centre_id',
        'chef_centre_telephone',
        'chef_centre_provenance',
        'chef_centre_categorie_personnel',
        'president_jury_id',
        'president_jury_telephone',
        'president_jury_provenance',
        'president_jury_categorie_personnel',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class, 'convocation_id');
    }

    public function chefCentre(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class, 'chef_centre_id');
    }

    /**
     * Contact academique du centre (distinct du chef de centre, contact
     * administratif) — cf. colonnes president_jury_id/president_jury_telephone.
     */
    public function presidentJury(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class, 'president_jury_id');
    }

    /**
     * Membres du jury affectes a CE centre precis, au sein de la
     * convocation (voir la colonne centre_id sur convocation_enseignant).
     *
     * 'categorie_personnel' est une colonne du pivot
     * `convocation_enseignant` depuis la migration
     * 2026_08_15_100000_add_categorie_personnel_to_convocation_enseignant_table
     * (avant elle, l'ajouter ici cassait tout eager-load "centres.enseignants"
     * avec SQLSTATE 42S22, colonne inexistante — d'où l'avertissement
     * précédent). Sans elle dans withPivot(), la colonne "Statut" de la
     * fiche convocation restait vide même quand la valeur était bien
     * enregistrée en base — même bug corrigé sur Convocations::enseignants()
     * et ConvocationCentreMetier::enseignants().
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_metier_id', 'provenance', 'categorie_personnel')->withTimestamps();
    }

    /**
     * Metiers/specialites couverts par ce centre (un centre peut en
     * couvrir plusieurs, chacun avec ses propres membres du jury — cf.
     * ConvocationCentreMetier).
     */
    public function metiers(): HasMany
    {
        return $this->hasMany(ConvocationCentreMetier::class, 'convocation_centre_id');
    }
}