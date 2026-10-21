<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Depot groupe des pieces justificatives d'UN membre (modale "Ajouter une
 * piece" sur la page Pieces justificatives) : les 5 fichiers manuels sont
 * tous obligatoires — le dossier d'un membre doit compter 6 documents au
 * total avant l'enregistrement (voir demande utilisateur), le 6e
 * ("dossier_convocation") completant automatiquement les 5 ici presents,
 * puisqu'il n'est jamais televerse manuellement — voir
 * PieceJustificativesController::deposerLot().
 */
class DeposerLotPiecesJustificativesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'convocation_id' => ['required', 'integer', 'exists:convocations,id'],
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'centre_id' => ['nullable', 'integer', 'exists:convocation_centres,id'],

            // 100 Ko max (voir demande utilisateur).
            'service_fait' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'ordre_mission' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'rapport_mission' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'bulletin_salaire' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'accuse_reception' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
        ];
    }
}
