<?php

namespace App\Http\Requests\Administration;

use App\Models\Admin\Role;
use App\Rules\CompatibleRoleStructure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matricule_enseignant' => [Rule::requiredIf(fn () => Role::whereKey($this->input('role_id'))->where('slug', 'enseignant')->exists()), 'nullable', 'string', Rule::exists('enseignants', 'matricule')->whereNull('deleted_at')],
            'telephone' => ['required', 'string', 'max:20'],
            'date_naiss' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'lieu_naissance' => ['sometimes', 'nullable', 'string', 'max:100'],
            'adresse' => ['sometimes', 'nullable', 'string', 'max:255'],
            'genre' => ['required', 'in:masculin,feminin'],

            'nom' => [
                'required',
                'string',
                'max:100'
            ],

            'prenom' => [
                'required',
                'string',
                'max:100'
            ],

            'email' => [
                'required',
                'email',
                'unique:users,email'
            ],

            'password' => [
                'required',
                'string',
                'min:8'
            ],

            'role_id' => [
                'required',
                'exists:roles,id'
            ],

            'statut' => [
                'required',
                'in:actif,inactif'
            ],

            'lieu_service_id' => [
                Rule::requiredIf(fn () => $this->roleMetier()),
                'nullable',
                'integer',
                Rule::exists('lieu_de_services', 'id')->where('est_actif', true),
                CompatibleRoleStructure::structureForRole($this->input('role_id')),
            ],

            'ia_id' => ['nullable', 'integer', 'exists:ias,id'],

        ];
    }

    private function roleMetier(): bool
    {
        $roleId = $this->input('role_id');

        return $roleId !== null
            && Role::whereKey($roleId)->whereHas('typeRole', fn ($query) => $query->where('code', '!=', 'systeme'))->exists();
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ];
    }
}
