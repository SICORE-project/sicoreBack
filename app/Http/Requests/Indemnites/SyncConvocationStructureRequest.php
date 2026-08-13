<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Fiche "Modifier" alignee sur l'assistant de creation (meme wizard,
 * pre-rempli — cf. convocation-wizard.js) : UNE seule requete remplace
 * TOUTE la structure de la convocation (infos generales + centres + leurs
 * metiers + membres du jury), plutot qu'une multitude de petits
 * formulaires postant chacun vers un endpoint different.
 *
 * "centres.*.id" / "centres.*.metiers.*.id" : presents = lignes
 * EXISTANTES a mettre a jour ; absents/vides = nouvelles lignes a creer.
 * Toute ligne existante NON presente dans la requete est supprimee
 * (ConvocationSyncController::sync() s'en charge) — semantique de
 * remplacement complet, comme un vrai "Enregistrer" de formulaire.
 */
class SyncConvocationStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Informations generales de la convocation (memes regles que
            // UpdateConvocationRequest, sans utilisateur_id qui ne doit pas
            // changer a l'edition).
            'type_convocation_id' => ['nullable', 'integer', 'exists:types_convocation,id'],
            'date_emission' => ['sometimes', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'objet' => ['sometimes', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:150'],
            'lieu_examen' => ['nullable', 'string', 'max:255'],
            'ordre_de_mission' => ['nullable', 'boolean'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'in:brouillon,emise,envoyee,cloturee'],

            // Centres d'examen — une convocation doit toujours en avoir au
            // moins un (cf. demande explicite de l'utilisatrice).
            'centres' => ['required', 'array', 'min:1'],
            'centres.*.id' => ['nullable', 'integer'],
            'centres.*.centre' => ['required', 'string', 'max:255'],
            'centres.*.jury' => ['nullable', 'string', 'max:100'],
            'centres.*.chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centres.*.chef_centre_telephone' => ['nullable', 'string', 'max:30'],
            'centres.*.president_jury_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centres.*.president_jury_telephone' => ['nullable', 'string', 'max:30'],

            // Metiers de CE centre (un centre peut en couvrir plusieurs,
            // chacun avec ses propres membres — cf. modele papier
            // "convocation jury" fourni par l'utilisatrice).
            'centres.*.metiers' => ['nullable', 'array'],
            'centres.*.metiers.*.id' => ['nullable', 'integer'],
            'centres.*.metiers.*.metier' => ['nullable', 'string', 'max:255'],

            // Membres du jury : rattaches a un centre ET, s'il y a lieu, a
            // un metier precis de ce centre, par leur POSITION dans les
            // tableaux ci-dessus ("centre_index" / "metier_index") — ils
            // n'ont pas encore d'id reel cote client pour les nouveaux
            // centres/metiers ajoutes dans le wizard.
            'beneficiaires' => ['nullable', 'array'],
            'beneficiaires.*.enseignant_id' => ['required_with:beneficiaires', 'integer', 'exists:enseignants,id'],
            'beneficiaires.*.fonction' => ['nullable', 'string', 'max:100'],
            'beneficiaires.*.provenance' => ['nullable', 'string', 'max:255'],
            'beneficiaires.*.categorie_personnel' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
            'beneficiaires.*.centre_index' => ['nullable', 'integer'],
            'beneficiaires.*.metier_index' => ['nullable', 'integer'],
        ];
    }
}
