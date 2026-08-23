<?php

namespace App\Http\Requests\Administration;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Rules\CompatibleRoleStructure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('id');
        $user = User::query()->find($userId);
        $roleId = $this->input('role_id', $user?->role_id);

        return [
            'nom'=>'sometimes|string|max:100',

            'prenom'=>'sometimes|string|max:100',

            'email'=>[
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'role_id' => [
                'sometimes',
                'exists:roles,id',
                CompatibleRoleStructure::roleForStructure(
                    $this->input('structure_organisationnelle_id', $user?->lieu_service_id)
                ),
            ],

            'statut'=>'sometimes|in:actif,inactif',

            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],

            'structure_organisationnelle_id' => [
                Rule::requiredIf(fn () => $this->structureRequise($userId)),
                'nullable',
                'integer',
                Rule::exists('lieu_de_services', 'id')->where('est_actif', true),
                CompatibleRoleStructure::structureForRole($roleId),
            ],

        ];
    }

    private function roleMetier(int|string|null $userId): bool
    {
        $roleId = $this->input('role_id')
            ?? User::query()->whereKey($userId)->value('role_id');

        return $roleId !== null
            && Role::whereKey($roleId)->whereHas('typeRole', fn ($query) => $query->where('code', '!=', 'systeme'))->exists();
    }

    private function structureRequise(int|string|null $userId): bool
    {
        if (! $this->roleMetier($userId)) {
            return false;
        }

        if ($this->has('structure_organisationnelle_id')) {
            return $this->input('structure_organisationnelle_id') === null
                || $this->input('structure_organisationnelle_id') === '';
        }

        return ! User::query()->whereKey($userId)
            ->whereNotNull('lieu_service_id')->exists();
    }
}
