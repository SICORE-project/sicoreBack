<?php

declare(strict_types=1);

namespace App\Http\Requests\indemnites;

use App\Enums\indemnites\AccuseReceptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccuseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('accuses_reception.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'document_id' => [
                'required',
                'integer',
                'exists:documents,id',
            ],

            'agent_deposant_id' => [
                'required',
                'integer',
                'exists:agents,id',
            ],

            'agent_receptionnaire_id' => [
                'required',
                'integer',
                'exists:agents,id',
            ],

            'date_depot' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                Rule::enum(AccuseReceptionStatus::class),
            ],
        ];
    }
}
