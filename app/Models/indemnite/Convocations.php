<?php

namespace App\Models\indemnite;

use App\Helpers\Indemnites\HasOpaqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Convocations extends Model
{
    use HasOpaqueSlug;

    protected $table = 'convocations';

    protected $fillable = [
        'type_convocation_id',
        'date_emission',
        'date_debut',
        'date_fin',
        'heure_debut',
        'objet',
        'session',
        'ordre_de_mission',
        'lieu_affectation',
        'fichier_chemin',
        'statut',
        'utilisateur_id',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'ordre_de_mission' => 'boolean',
    ];

    public function typeConvocation(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Indemnite\TypeConvocation::class,
            'type_convocation_id'
        );
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Admin\User::class,
            'utilisateur_id'
        );
    }

    /**
     * Bénéficiaires affectés à la convocation.
     *
     * 'categorie_personnel' est bien une colonne du pivot
     * `convocation_enseignant` (voir migration
     * 2026_08_15_100000_add_categorie_personnel_to_convocation_enseignant_table,
     * qui a remplacé une tentative précédente et annulée de l'ajouter sur
     * `enseignants`) : la catégorie est saisie par convocation, pas comme
     * attribut permanent de l'enseignant. Sans elle dans withPivot(), la
     * colonne "Statut" de la fiche convocation (show.blade.php) restait
     * vide même quand la valeur était bien enregistrée en base.
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'convocation_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_id', 'centre_metier_id', 'provenance', 'categorie_personnel')->withTimestamps();
    }


    public function centres(): HasMany
    {
        return $this->hasMany(ConvocationCentre::class, 'convocation_id');
    }

    public function envois(): HasMany
    {
        return $this->hasMany(
            ConvocationEnvoi::class,
            'convocation_id'
        );
    }

    /**
     * Nettoyage manuel a la suppression : convocation_centres,
     * convocation_centre_metiers, convocation_enseignant et
     * convocation_envois sont en MyISAM, moteur qui n'applique PAS les
     * contraintes de cle etrangere declarees dans les migrations
     * (cascadeOnDelete/nullOnDelete y sont silencieusement ignorees) — sans
     * ce hook, chaque suppression de convocation laisse des lignes
     * orphelines dans ces tables (constate en base : ~40 lignes orphelines
     * accumulees avant ce correctif).
     */
    protected static function booted(): void
    {
        static::deleting(function (self $convocation) {
            $centreIds = $convocation->centres()->pluck('id');

            DB::table('convocation_centre_metiers')
                ->whereIn('convocation_centre_id', $centreIds)
                ->delete();

            DB::table('convocation_enseignant')
                ->where('convocation_id', $convocation->id)
                ->delete();

            // indemnites_correction est en MyISAM comme le reste de ces
            // tables (cascadeOnDelete() declare en migration mais ignore
            // silencieusement par ce moteur) — meme nettoyage manuel que
            // pour convocation_centre_metiers/convocation_enseignant
            // ci-dessus, sans quoi supprimer une convocation laisserait des
            // indemnites de correction orphelines.
            DB::table('indemnites_correction')
                ->where('convocation_id', $convocation->id)
                ->delete();

            $convocation->centres()->delete();
            $convocation->envois()->delete();
        });
    }
}
