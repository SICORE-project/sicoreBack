<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,

            'region_id' => $this->region_id,

            'region' => $this->whenLoaded('region', function () {
                return [
                    'id' => $this->region->id,
                    'code' => $this->region->code,
                    'nom' => $this->region->nom,
                ];
            }),

            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'responsable' => $this->responsable,

            'est_actif' => (bool) $this->est_actif,

            'nom_complet' => $this->nom_complet,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}