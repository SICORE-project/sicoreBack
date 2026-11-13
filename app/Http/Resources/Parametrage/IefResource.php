<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,

            'ia_id' => $this->ia_id,

            'ia' => $this->whenLoaded('ia', function () {
                return [
                    'id' => $this->ia->id,
                    'code' => $this->ia->code,
                    'libelle' => $this->ia->libelle,
                ];
            }),

            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'responsable' => $this->responsable,

            'nom_complet' => $this->nom_complet,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
