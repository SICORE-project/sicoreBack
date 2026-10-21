<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutFinancierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'sigle' => $this->sigle,
            'type_institution' => $this->type_institution,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse' => $this->adresse,
            'code_banque' => $this->code_banque,
            'code_guichet' => $this->code_guichet,
            'iban_exemple' => $this->iban_exemple,
            'est_actif' => $this->est_actif,
        ];
    }
}
