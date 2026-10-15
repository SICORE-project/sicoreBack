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
            'is_online' => $this->statut === 'actif' && \App\Services\Auth\UserPresence::online('user', $this->id),
            'enseignant_id' => $this->enseignant_id,
            'password_defined' => $this->password_changed_at !== null,
            'matricule' => $this->whenLoaded('enseignant', fn () => $this->enseignant?->matricule),
            'affectation' => $this->when($this->relationLoaded('enseignant'), function () {
                $teacher = $this->enseignant;
                $place = $teacher ? $teacher->lieuService : $this->lieuService;
                return [
                    'ia' => $teacher ? $teacher->ia?->libelle : ($this->ia?->libelle ?? $place?->ia?->libelle),
                    'ief' => $teacher ? $teacher->ief?->libelle : ($this->ief?->libelle ?? $place?->ief?->libelle),
                    'etablissement' => $place?->libelle,
                ];
            }),

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

            'permissions' => $this->permissionSlugs(),

            'ia_id' => $this->ia_id,

            'ief_id' => $this->ief_id,

            'acces_organisationnel' => $this->perimetre(),

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

            'derniere_connexion' => $this->derniere_connexion?->timezone('Africa/Dakar')->format('d/m/Y H:i'),

            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

        ];
    }
}
