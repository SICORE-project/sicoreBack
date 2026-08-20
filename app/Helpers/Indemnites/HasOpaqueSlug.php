<?php

namespace App\Helpers\Indemnites;

use Illuminate\Database\Eloquent\Builder;

/**
 * Identifiant public opaque (12 caracteres hex aleatoires, cf. migration
 * ..._add_slug_to_convocations_and_related_tables) genere automatiquement a
 * la creation, utilise a la place de l'id numerique dans toutes les URLs
 * cote front (show/edit/pdf/centres/metiers...) — evite d'exposer l'id
 * sequentiel (et donc, par exemple, le nombre total de convocations) dans
 * un lien visible/partageable. L'id numerique reste la cle primaire reelle
 * pour toutes les relations internes (FK, pivot...), inchangee.
 *
 * scopeSlugOuId()/trouverParSlugOuId() acceptent AUSSI l'id numerique en
 * plus du slug, pour ne rien casser cote back (relations internes, anciens
 * liens) — seule la partie front doit desormais construire ses liens avec
 * le slug plutot que l'id.
 *
 * Utilise par les modeles du module Indemnites : Convocations,
 * ConvocationCentre, ConvocationCentreMetier (app/Models/Indemnite/).
 */
trait HasOpaqueSlug
{
    protected static function bootHasOpaqueSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::genererSlugUnique();
            }
        });
    }

    public static function genererSlugUnique(): string
    {
        do {
            $slug = bin2hex(random_bytes(6));
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Query scope : filtre par slug OU id numerique. 
     */
    public function scopeSlugOuId(Builder $query, int|string $valeur): Builder
    {
        return $query->where(function (Builder $q) use ($valeur) {
            $q->where($this->getTable().'.slug', $valeur);

            if (is_numeric($valeur)) {
                $q->orWhere($this->getTable().'.id', $valeur);
            }
        });
    }

    public static function trouverParSlugOuId(int|string $valeur): ?static
    {
        return static::query()->slugOuId($valeur)->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
