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

            'derniere_connexion' => $this->derniere_connexion?->timezone('Africa/Dakar')->format('d/m/Y H:i'),

            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),

        ];
    }
}
