<?php

namespace App\Http\Resources\Personnel;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Compte bancaire principal
        |--------------------------------------------------------------------------
        */

        $comptePrincipal = null;

        if ($this->relationLoaded('comptesBancaires')) {
            $comptePrincipal = $this->comptesBancaires
                ->firstWhere('est_principal', true);

            if (!$comptePrincipal) {
                $comptePrincipal = $this->comptesBancaires->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Syndicat actif
        |--------------------------------------------------------------------------
        */

        $syndicat = null;

        if ($this->relationLoaded('syndicats')) {
            $syndicat = $this->syndicats
                ->first(
                    fn ($item) =>
                        (bool) $item->pivot?->est_actif
                );

            if (!$syndicat) {
                $syndicat = $this->syndicats->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mutuelle active
        |--------------------------------------------------------------------------
        */

        $mutuelle = null;

        if ($this->relationLoaded('mutuelles')) {
            $mutuelle = $this->mutuelles
                ->first(
                    fn ($item) =>
                        (bool) $item->pivot?->est_actif
                );

            if (!$mutuelle) {
                $mutuelle = $this->mutuelles->first();
            }
        }

        return [

            // =====================================================
            // IDENTITÉ
            // =====================================================

            'id' => $this->id,

            'matricule' => $this->matricule,

            'nom' => $this->nom,

            'prenom' => $this->prenom,

            'categorie_personnel' => $this->categorie_personnel,

            'nom_complet' => $this->nom_complet,

            'date_naissance' =>
                $this->date_naissance?->format('Y-m-d'),

            'lieu_naissance' =>
                $this->lieu_naissance,

            'genre' =>
                $this->genre,

            'telephone' =>
                $this->telephone,

            'email' =>
                $this->email,

            'adresse' =>
                $this->adresse,

            'cni' => $this->cni,
            'salaire_brut' => $this->salaire_brut,
            'generation' => $this->generation,
            'date_prise_service' => $this->date_prise_service?->format('Y-m-d'),
            'date_fin_contrat' => $this->date_fin_contrat?->format('Y-m-d'),
            'est_en_couple' => (bool) $this->est_en_couple,
            'nombre_enfants' => (int) $this->nombre_enfants,
            'nombre_femmes' => (int) $this->nombre_femmes,
            'nombre_parts_fiscales' => (float) $this->nombre_parts_fiscales,
            'conjoint_travaille' => (bool) $this->conjoint_travaille,
            'observations' => $this->observations,

            'diplome' => $this->whenLoaded('diplome', fn () => $this->diplome ? [
                'id' => $this->diplome->id,
                'libelle' => $this->diplome->libelle,
                'categorie_id' => $this->diplome->categorie_id,
                'salaire_brut' => (float) $this->diplome->salaire_brut,
            ] : null),

            'lieu_service' => $this->whenLoaded('lieuService', fn () => $this->lieuService ? [
                'id' => $this->lieuService->id,
                'code' => $this->lieuService->code,
                'libelle' => $this->lieuService->libelle,
            ] : null),

            // =====================================================
            // RECRUTEMENT
            // =====================================================

            'date_recrutement' =>
                $this->date_recrutement?->format('Y-m-d'),

            'annee_recrutement' =>
                $this->annee_recrutement,

            // =====================================================
            // IA
            // =====================================================

            'ia' => $this->whenLoaded(
                'ia',
                fn () => $this->ia
                    ? [
                        'id' =>
                            $this->ia->id,

                        'code' =>
                            $this->ia->code,

                        'libelle' =>
                            $this->ia->libelle,
                    ]
                    : null
            ),

            // =====================================================
            // IEF
            // =====================================================

            'ief' => $this->whenLoaded(
                'ief',
                fn () => $this->ief
                    ? [
                        'id' =>
                            $this->ief->id,

                        'code' =>
                            $this->ief->code,

                        'libelle' =>
                            $this->ief->libelle,
                    ]
                    : null
            ),

            // =====================================================
            // CORPS
            // =====================================================

            'corps' => $this->whenLoaded(
                'corps',
                fn () => $this->corps
                    ? [
                        'id' =>
                            $this->corps->id,

                        'code' =>
                            $this->corps->code,

                        'libelle' =>
                            $this->corps->libelle,
                    ]
                    : null
            ),

            // =====================================================
            // GRADE
            // =====================================================

            'grade' => $this->whenLoaded(
                'grade',
                fn () => $this->grade
                    ? [
                        'id' =>
                            $this->grade->id,

                        'code' =>
                            $this->grade->code,

                        'libelle' =>
                            $this->grade->libelle,
                    ]
                    : null
            ),

            'categorie' => $this->whenLoaded(
                'categorie',
                fn () => $this->categorie
                    ? [
                        'id' => $this->categorie->id,
                        'libelle' => $this->categorie->libelle,
                    ]
                    : null
            ),

            // =====================================================
            // DISCIPLINE
            // =====================================================

            'discipline' => $this->whenLoaded(
                'discipline',
                fn () => $this->discipline
                    ? [
                        'id' =>
                            $this->discipline->id,

                        'code' =>
                            $this->discipline->code,

                        'libelle' =>
                            $this->discipline->libelle,
                    ]
                    : null
            ),

            // =====================================================
            // COMPTE BANCAIRE
            // =====================================================

            'compte_bancaire' => $comptePrincipal
                ? [
                    'id' =>
                        $comptePrincipal->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Institut financier
                    |--------------------------------------------------------------------------
                    |
                    | La vraie table utilise :
                    |
                    | code
                    | libelle
                    |
                    | et non :
                    |
                    | nom
                    | sigle
                    |
                    */

                    'institut_financier' =>
                        $comptePrincipal
                            ->relationLoaded('institutFinancier')
                        && $comptePrincipal->institutFinancier
                            ? [
                                'id' =>
                                    $comptePrincipal
                                        ->institutFinancier
                                        ->id,

                                'libelle' =>
                                    $comptePrincipal
                                        ->institutFinancier
                                        ->libelle,
                            ]
                            : null,

                    'code_banque' =>
                        $comptePrincipal->code_banque,

                    'code_guichet' =>
                        $comptePrincipal->code_guichet,

                    'numero_compte' =>
                        $comptePrincipal->numero_compte,

                    'cle_rib' =>
                        $comptePrincipal->cle_rib,

                    'iban' =>
                        $comptePrincipal->iban,

                    'bic' =>
                        $comptePrincipal->bic,

                    'titulaire_compte' =>
                        $comptePrincipal->titulaire_compte,

                    'type_virement' =>
                        $comptePrincipal->type_virement,

                    'est_principal' =>
                        (bool) $comptePrincipal->est_principal,

                    'est_actif' =>
                        (bool) $comptePrincipal->est_actif,
                ]
                : null,

            // =====================================================
            // SYNDICAT
            // =====================================================

            'syndicat' => $syndicat
                ? [
                    'id' =>
                        $syndicat->id,

                    'code' =>
                        $syndicat->code,

                    'nom' =>
                        $syndicat->nom,

                    'sigle' =>
                        $syndicat->sigle,

                    'numero_affiliation' =>
                        $syndicat
                            ->pivot
                            ?->numero_affiliation,

                    'taux_personnalise' =>
                        $syndicat
                            ->pivot
                            ?->taux_personnalise,

                    'date_adhesion' =>
                        $syndicat
                            ->pivot
                            ?->date_adhesion,

                    'date_resiliation' =>
                        $syndicat
                            ->pivot
                            ?->date_resiliation,

                    'est_actif' =>
                        (bool) $syndicat
                            ->pivot
                            ?->est_actif,
                ]
                : null,

            // =====================================================
            // MUTUELLE
            // =====================================================

            'mutuelle' => $mutuelle
                ? [
                    'id' =>
                        $mutuelle->id,

                    'code' =>
                        $mutuelle->code,

                    'nom' =>
                        $mutuelle->nom,

                    'sigle' =>
                        $mutuelle->sigle,

                    'numero_affiliation' =>
                        $mutuelle
                            ->pivot
                            ?->numero_affiliation,

                    'date_adhesion' =>
                        $mutuelle
                            ->pivot
                            ?->date_adhesion,

                    'date_resiliation' =>
                        $mutuelle
                            ->pivot
                            ?->date_resiliation,

                    'est_actif' =>
                        (bool) $mutuelle
                            ->pivot
                            ?->est_actif,
                ]
                : null,

            // =====================================================
            // STATUT
            // =====================================================

            'statut' =>
                $this->statut,

            'statut_libelle' =>
                $this->statut_libelle,

            'est_actif' =>
                (bool) $this->est_actif,

            // =====================================================
            // MÉTADONNÉES
            // =====================================================

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}
