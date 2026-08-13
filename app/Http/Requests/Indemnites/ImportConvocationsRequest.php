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
            // Fichier remis par la DECPC (option A du workflow DAGE). CSV,
            // ou Word (.docx) si le tableau de convocation est fourni sous
            // forme de document (cf. ConvocationImportController — parsing
            // via phpoffice/phpword, 1re ligne du 1er tableau = en-têtes,
            // mêmes alias de colonnes que le format CSV).
            'fichier' => ['required', 'file', 'mimes:csv,txt,docx', 'max:5120'],

            // La DAGE qui réalise l'import — les convocations créées lui
            // sont rattachées (utilisateur_id, non nullable en base).
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
