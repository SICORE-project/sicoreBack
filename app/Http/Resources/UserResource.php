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
            $this->mergeWhen($this->resource->hasRole('agent_decpc'), fn () => [
                'decpc' => app(\App\Services\Administration\DecpcDashboard::class)->context($this->resource),
            ]),
            $this->mergeWhen($this->resource->isDrh(), fn () => [
                'drh' => app(\App\Services\Administration\Personnel\DrhDashboard::class)->context($this->resource),
            ]),

            'id' => $this->id,
            'matricule_enseignant' => $this->enseignant?->matricule,
            'ia_id' => $this->ia_id,
            'ief_id' => $this->ief_id,

            'nom' => $this->nom,

            'prenom' => $this->prenom,

            'nom_complet' => $this->prenom.' '.$this->nom,

            'email' => $this->email,

            'genre' => $this->genre,

            'date_naiss' => $this->date_naiss?->format('d/m/Y'),
            'date_naiss_iso' => $this->date_naiss?->format('Y-m-d'),

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

            'ia' => $this->ia ? [
                'id' => $this->ia->id,
                'libelle' => $this->ia->libelle,
            ] : null,

            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

        ];
    }
}
