<?php

namespace App\Http\Requests\Parametrage\Ief;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIefRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $iefId = $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('iefs', 'code')->ignore($iefId),
            ],

            'libelle' => [
                'required',
                'string',
                'max:100',
            ],

            'ia_id' => [
                'required',
                'integer',
                'exists:ias,id',
            ],

            'adresse' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(?:\+221|221)?(70|75|76|77|78)[0-9]{7}$/',
            ],

            'email' => [
                'nullable',
                'email',
                'max:100',
            ],

            'responsable' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de l’IEF est obligatoire.',
            'code.max' => 'Le code de l’IEF ne doit pas dépasser 20 caractères.',
            'code.regex' => 'Le format du code IEF est invalide.',
            'code.unique' => 'Ce code IEF existe déjà.',

            'libelle.required' => 'Le libellé de l’IEF est obligatoire.',
            'libelle.max' => 'Le libellé de l’IEF ne doit pas dépasser 100 caractères.',

            'ia_id.required' => 'L’Inspection d’Académie est obligatoire.',
            'ia_id.integer' => 'L’Inspection d’Académie sélectionnée est invalide.',
            'ia_id.exists' => 'L’Inspection d’Académie sélectionnée n’existe pas.',

            'telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide.',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères.',

            'email.email' => 'L’adresse email n’est pas valide.',
            'email.max' => 'L’adresse email ne doit pas dépasser 100 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->filled('code')) {
            $data['code'] = strtoupper(trim($this->code));
        }

        if ($this->filled('telephone')) {
            $data['telephone'] = preg_replace(
                '/[\s\-.]/',
                '',
                trim($this->telephone)
            );
        }

        $this->merge($data);
    }
}