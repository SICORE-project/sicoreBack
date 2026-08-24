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

        ];
    }

    private function roleMetier(): bool
    {
        $roleId = $this->input('role_id');

        return $roleId !== null
            && Role::whereKey($roleId)->whereHas('typeRole', fn ($query) => $query->where('code', '!=', 'systeme'))->exists();
    }
}
