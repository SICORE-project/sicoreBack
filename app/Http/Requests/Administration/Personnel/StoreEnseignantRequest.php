<?php

namespace App\Http\Requests\Administration\Personnel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // =========================
            // IDENTITÉ
            // =========================

            'matricule' => [
                'required',
                'string',
                'max:30',
                'unique:enseignants,matricule',
            ],

            'nom' => [
                'required',
                'string',
                'max:50',
            ],

            'prenom' => [
                'required',
                'string',
                'max:50',
            ],

            'date_naissance' => [
                'nullable',
                'date',
                'before:today',
            ],

            'lieu_naissance' => [
                'nullable',
                'string',
                'max:100',
            ],

            'genre' => [
                'nullable',
                Rule::in([
                    'M',
                    'F',
                    'masculin',
                    'feminin',
                ]),
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:100',
            ],

            'adresse' => [
                'nullable',
                'string',
                'max:255',
            ],

            // =========================
            // ADMINISTRATION
            // =========================

            'date_recrutement' => [
                'nullable',
                'date',
            ],

            'ia_id' => [
                'nullable',
                'integer',
                'exists:ias,id',
            ],

            'ief_id' => [
                'nullable',
                'integer',
                'exists:iefs,id',
            ],

            'corps_id' => [
                'nullable',
                'integer',
                'exists:corps_enseignant,id',
            ],

            'grade_id' => [
                'nullable',
                'integer',
                'exists:grades,id',
            ],

            'discipline_id' => [
                'nullable',
                'integer',
                'exists:disciplines,id',
            ],

            'statut_enseignant_id' => [
                'nullable',
                'integer',
                'exists:statuts_enseignant,id',
            ],

            'statut' => [
                'required',
                Rule::in([
                    'en_activite',
                    'retraite',
                    'suspension_provisoire',
                    'abandon',
                    'decede',
                    'integre',
                    'radie',
                    'cessation_paiement',
                ]),
            ],

            'est_actif' => [
                'required',
                'boolean',
            ],

            // =========================
            // COMPTE BANCAIRE
            // =========================

            'compte_bancaire' => [
                'nullable',
                'array',
            ],

            'compte_bancaire.institut_financier_id' => [
                'nullable',
                'integer',
                'exists:instituts_financieres,id',
            ],

            'compte_bancaire.code_banque' => [
                'nullable',
                'string',
                'max:5',
            ],

            'compte_bancaire.code_guichet' => [
                'nullable',
                'string',
                'max:5',
            ],

            'compte_bancaire.numero_compte' => [
                'nullable',
                'string',
                'max:11',
],

            'compte_bancaire.cle_rib' => [
                'nullable',
                'string',
                'max:2',
            ],

            'compte_bancaire.iban' => [
                'nullable',
                'string',
                'max:34',
            ],

            'compte_bancaire.bic' => [
                'nullable',
                'string',
                'max:11',
            ],

            'compte_bancaire.titulaire_compte' => [
                'nullable',
                'string',
                'max:100',
            ],

            'compte_bancaire.type_virement' => [
                'nullable',
                Rule::in([
                'unitaire',
                'masse',
                ]),
            ],

            

            'compte_bancaire.est_principal' => [
                'nullable',
                'boolean',
            ],

            // =========================
            // SYNDICAT
            // =========================

            'syndicat' => [
                'nullable',
                'array',
            ],

            'syndicat.syndicat_id' => [
                'nullable',
                'integer',
                'exists:syndicats,id',
            ],

            'syndicat.taux_personnalise' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'syndicat.date_adhesion' => [
                'nullable',
                'date',
            ],

            'syndicat.date_resiliation' => [
                'nullable',
                'date',
                'after_or_equal:syndicat.date_adhesion',
            ],

            'syndicat.numero_affiliation' => [
                'nullable',
                'string',
                'max:50',
            ],

            // =========================
            // MUTUELLE
            // =========================

            'mutuelle' => [
                'nullable',
                'array',
            ],

            'mutuelle.mutuelle_id' => [
                'nullable',
                'integer',
                'exists:mutuelles,id',
            ],

            'mutuelle.numero_affiliation' => [
                'nullable',
                'string',
                'max:50',
            ],

            'mutuelle.date_adhesion' => [
                'nullable',
                'date',
            ],

            'mutuelle.date_resiliation' => [
                'nullable',
                'date',
                'after_or_equal:mutuelle.date_adhesion',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.required' =>
                'Le matricule est obligatoire.',

            'matricule.unique' =>
                'Ce matricule existe déjà.',

            'nom.required' =>
                'Le nom est obligatoire.',

            'prenom.required' =>
                'Le prénom est obligatoire.',

            'date_naissance.before' =>
                'La date de naissance doit être antérieure à aujourd’hui.',

            'genre.in' =>
                'Le genre doit être M, F, masculin ou feminin.',

            'ia_id.exists' =>
                'L’IA sélectionnée n’existe pas.',

            'ief_id.exists' =>
                'L’IEF sélectionnée n’existe pas.',

            'corps_id.exists' =>
                'Le corps sélectionné n’existe pas.',

            'grade_id.exists' =>
                'Le grade sélectionné n’existe pas.',

            'discipline_id.exists' =>
                'La discipline sélectionnée n’existe pas.',

            'statut_enseignant_id.exists' =>
                'Le statut enseignant sélectionné n’existe pas.',

            'compte_bancaire.institut_financier_id.exists' =>
                'L’institut financier sélectionné n’existe pas.',
            
            'compte_bancaire.numero_compte.max' =>
                'Le numéro de compte ne doit pas dépasser 11 caractères.',

            'syndicat.syndicat_id.exists' =>
                'Le syndicat sélectionné n’existe pas.',

            'mutuelle.mutuelle_id.exists' =>
                'La mutuelle sélectionnée n’existe pas.',
        ];
    }
}
