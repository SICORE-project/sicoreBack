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

            'statut' => $this->statut,

            'role' => [
                'id' => $this->role?->id,
                'nom' => $this->role?->nom,
                'slug' => $this->role?->slug,
            ],

            'structure_organisationnelle' => $this->structureOrganisationnelle ? [
                'id' => $this->structureOrganisationnelle->id,
                'type' => $this->structureOrganisationnelle->type,
                'libelle' => $this->structureOrganisationnelle->libelle,
            ] : null,

            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

        ];
    }
}
