<?php

declare(strict_types=1);

namespace App\Http\Requests\indemnites;

use App\Enums\indemnites\AccuseReceptionStatus;
use App\Models\indemnites\Accuse_receptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccuseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Accuse_receptions|null $accuse */
        $accuse = $this->route('accuseReception');

        return $accuse !== null
            && ($this->user()?->can('update', $accuse) ?? false);
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
