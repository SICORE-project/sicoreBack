<?php

namespace App\Http\Requests\Parametrage\Ief;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateIefRequest extends FormRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $iefId = $this->route('id');
            $currentIa = DB::table('iefs')->where('id', $iefId)->value('ia_id');
            if ((int) $currentIa === $this->integer('ia_id')) {
                return;
            }
            if (DB::table('lieu_de_services')->where('ief_id', $iefId)->exists()
                || DB::table('enseignants')->where('ief_id', $iefId)->exists()) {
                $validator->errors()->add('ia_id', 'Impossible de changer l’IA d’une IEF liée à des établissements ou à des enseignants.');
            }
        }];
    }

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
        $data = [
            'libelle' => trim((string) $this->input('libelle')),
        ];

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
