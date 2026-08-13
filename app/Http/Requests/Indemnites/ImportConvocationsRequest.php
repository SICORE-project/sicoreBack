<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class ImportConvocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Fichier remis par la DECPC (option A du workflow DAGE). CSV
            // uniquement pour cette première version (pas de dépendance
            // xlsx/maatwebsite ajoutée à composer.json) — voir
            // GUIDE-IMPORT-CONVOCATIONS.md pour le format attendu.
            'fichier' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],

            // La DAGE qui réalise l'import — les convocations créées lui
            // sont rattachées (utilisateur_id, non nullable en base).
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
