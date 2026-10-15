<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtatPaieIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Les dates de période peuvent arriver au format ISO (2026-11-16T00:00:00Z) : ramenées à AAAA-MM-JJ. */
    protected function prepareForValidation(): void
    {
        foreach (['periode_debut', 'periode_fin'] as $champ) {
            if (! $this->filled($champ)) {
                continue;
            }

            try {
                $this->merge([$champ => \Illuminate\Support\Carbon::parse($this->input($champ))->toDateString()]);
            } catch (\Throwable) {
                // Laisse la règle « date » signaler la valeur invalide.
            }
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'beneficiaire_id' => ['nullable', 'integer', 'exists:users,id'],
            'periode_debut' => ['required', 'date'],
            'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'],
            'lieu_examen' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:100'],
            'perimetre' => ['nullable', 'array'],
            'details' => ['nullable', 'array'],
            'details.*.montant' => ['nullable', 'numeric', 'min:0'],
            // Chaque ligne conserve toutes ses informations (sinon validated() ne garderait que le montant).
            'details.*.id' => ['nullable', 'integer'],
            'details.*.nom' => ['nullable', 'string', 'max:100'],
            'details.*.prenom' => ['nullable', 'string', 'max:100'],
            'details.*.categorie_personnel' => ['nullable', 'string', 'max:50'],
            'details.*.fonction' => ['nullable', 'string', 'max:100'],
            'details.*.metier' => ['nullable', 'string', 'max:255'],
            'details.*.centre' => ['nullable', 'string', 'max:255'],
            'details.*.code_banque' => ['nullable', 'string', 'max:20'],
            'details.*.code_guichet' => ['nullable', 'string', 'max:20'],
            'details.*.numero_compte_bancaire' => ['nullable', 'string', 'max:40'],
            'details.*.cle_rib' => ['nullable', 'string', 'max:10'],
        ];
    }
}
