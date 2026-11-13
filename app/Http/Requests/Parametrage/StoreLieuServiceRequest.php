<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Parametrage\LieuService;
use Illuminate\Support\Str;

class StoreLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'telephone' => ['nullable', 'string', 'max:20'],
            'libelle' => ['bail', 'required', 'string', 'max:100', function ($attribute, $value, $fail): void {
                if (! $this->filled('ief_id')) return;
                $current = $this->route('lieuService');
                $currentId = $current instanceof LieuService ? $current->getKey() : $current;
                $duplicate = LieuService::query()->where('ief_id', $this->input('ief_id'))
                    ->when($currentId, fn ($query) => $query->whereKeyNot($currentId))
                    ->get(['libelle'])
                    ->contains(fn ($lieu) => $this->normalizeLabel($lieu->libelle) === $this->normalizeLabel($value));
                if ($duplicate) {
                    $fail('Un établissement portant ce libellé existe déjà dans cette IEF. Modifiez l’établissement existant au lieu de le créer à nouveau.');
                }
            }],
            'ia_id' => [
                'required',
                'integer',
                Rule::exists('ias', 'id')->whereNull('deleted_at'),
            ],
            'ief_id' => [
                'required',
                'integer',
                Rule::exists('iefs', 'id')
                    ->where(fn ($query) => $query
                        ->where('ia_id', $this->input('ia_id'))
                        ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le nom de l’établissement est obligatoire.',
            'ia_id.required' => 'L’IA est obligatoire.',
            'ief_id.required' => 'L’IEF est obligatoire.',
            'ia_id.exists' => 'L’inspection d’académie sélectionnée est introuvable.',
            'ief_id.exists' => 'L’IEF sélectionnée est introuvable ou n’appartient pas à cette inspection d’académie.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('libelle'))) {
            $this->merge(['libelle' => preg_replace('/\s+/u', ' ', trim($this->input('libelle')))]);
        }
    }

    private function normalizeLabel(string $label): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/u', ' ', trim($label))));
    }
}
