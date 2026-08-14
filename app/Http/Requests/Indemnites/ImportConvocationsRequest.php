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
            // Modèle Word (.docx) téléchargeable depuis la liste des
            // convocations (voir ConvocationModeleWordController) — un
            // document rempli décrit UNE convocation complète (infos +
            // centres + membres du jury), cf. ConvocationWordTemplateService.
            'fichier' => ['required', 'file', 'mimes:docx', 'max:5120'],

            // Choisi dans le formulaire d'import (modal), pas dans le
            // document Word — voir ConvocationWordTemplateService.
            'type_convocation_id' => ['required', 'integer', 'exists:types_convocation,id'],

            // La DAGE qui réalise l'import — les convocations créées lui
            // sont rattachées (utilisateur_id, non nullable en base).
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
