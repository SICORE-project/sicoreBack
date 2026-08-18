<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation dédiée au dépôt de fichier d'une convocation existante
 * (POST /convocations/{id}/fichier). Séparée de StoreConvocationRequest
 * pour éviter la bifurcation conditionnelle qui existait auparavant.
 */
class UploadFichierConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
