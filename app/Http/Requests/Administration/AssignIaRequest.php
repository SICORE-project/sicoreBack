<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class AssignIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'ia_id' => 'required|exists:ias,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'ia_id.required' => 'L\'IA est obligatoire.',
            'ia_id.exists' => 'L\'IA sélectionnée n\'existe pas.',
        ];
    }
}