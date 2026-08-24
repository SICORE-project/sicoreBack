<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification du dossier d'UN membre (modale "Modifier", meme formulaire
 * complet que "Ajouter une piece" — demande utilisatrice) : contrairement a
 * DeposerLotPiecesJustificativesRequest, les 5 fichiers sont TOUS
 * optionnels — seuls ceux fournis remplacent la piece existante (voir
 * PieceJustificativesController::traiterLot()), les autres restent tels
 * quels (deja deposes precedemment, l'utilisatrice ne veut pas forcement
 * tous les reteleverser pour n'en corriger qu'un seul).
 */
class ModifierLotPiecesJustificativesRequest extends FormRequest
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

            // 100 Ko max (voir demande utilisateur) — tous optionnels ici.
            'service_fait' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'ordre_mission' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'rapport_mission' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'bulletin_salaire' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
            'accuse_reception' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
        ];
    }
}
