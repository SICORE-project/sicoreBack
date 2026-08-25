<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'nom' => $this->nom,

            'prenom' => $this->prenom,

            'nom_complet' => $this->prenom.' '.$this->nom,

            'email' => $this->email,

            'genre' => $this->genre,

            'date_naiss' => $this->date_naiss?->format('d/m/Y'),

            'lieu_naissance' => $this->lieu_naissance,

            'telephone' => $this->telephone,

            'adresse' => $this->adresse,

            'fonction' => $this->fonction,

            'statut' => $this->statut,

            'role' => [
                'id' => $this->role?->id,
                'nom' => $this->role?->nom,
                'slug' => $this->role?->slug,
            ],

            'lieu_service' => $this->lieuService ? [
                'id' => $this->lieuService->id,
                'code' => $this->lieuService->code,
                'type' => $this->lieuService->type,
                'libelle' => $this->lieuService->libelle,
            ] : null,

            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

        ];
    }
}
