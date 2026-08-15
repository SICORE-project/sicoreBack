<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvocationCentresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'centres' => ['required', 'array', 'min:1'],
            'centres.*.centre' => ['required', 'string', 'max:255'],
            'centres.*.jury' => ['nullable', 'string', 'max:100'],
            // 'metier' (singulier) = retrocompatibilite avec le formulaire
            // "Ajouter le centre" de edit.blade.php (1 seul champ). 'metiers'
            // (tableau) = wizard de creation, qui peut soumettre plusieurs
            // metiers d'un coup pour UN MEME centre (cf. ConvocationCentreController::store()).
            'centres.*.metier' => ['nullable', 'string', 'max:255'],
            'centres.*.metiers' => ['nullable', 'array'],
            'centres.*.metiers.*' => ['nullable', 'string', 'max:255'],
            'centres.*.chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centres.*.chef_centre_telephone' => ['nullable', 'string', 'max:30'],
            'centres.*.president_jury_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centres.*.president_jury_telephone' => ['nullable', 'string', 'max:30'],
        ];
    }
}