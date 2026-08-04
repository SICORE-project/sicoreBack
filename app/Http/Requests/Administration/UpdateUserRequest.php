<?php

namespace App\Http\Requests\Administration;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'nom'=>'sometimes|string|max:100',

            'prenom'=>'sometimes|string|max:100',

            'email'=>[
                'sometimes',
                'email',
                'unique:users,email,'.$userId
            ],

            'role_id'=>'sometimes|exists:roles,id',

            'statut'=>'sometimes|in:actif,inactif',

        ];
    }
}
