<?php

namespace App\Services\Administration\Personnel;

use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;

class DrhDashboard
{
    public function context(User $user): array
    {
        $permissions = $user->role?->permissions()->pluck('slug')->all() ?? [];
        $modules = [];
        foreach ([
            'personnel' => ['Gestion du personnel', 'enseignants.read'],
            'indemnites' => ['Gestion des indemnités', 'indemnites.read'],
            'utilisateurs' => ['Gestion des utilisateurs', 'administration.users.read'],
        ] as $key => [$label, $permission]) {
            if (in_array($permission, $permissions, true)) {
                $modules[] = ['key' => $key, 'label' => $label];
            }
        }
        if (in_array($user->role?->slug, config('payroll.read_roles', []), true) && $user->tokenCan('payroll:read')) {
            $modules[] = ['key' => 'paie', 'label' => 'Gestion de la paie'];
        }
        if (collect($permissions)->contains(fn ($slug) => str_starts_with($slug, 'parametrage.'))) {
            $modules[] = ['key' => 'parametrage', 'label' => 'Paramétrage'];
        }

        return [
            'dashboard_path' => '/drh/dashboard',
            'dashboard_api' => '/api/drh/dashboard',
            'perimetre' => app(DrhScope::class)->describe($user),
            'permissions' => $permissions,
            'modules' => $modules,
        ];
    }

    public function data(User $user): array
    {
        $query = app(DrhScope::class)->apply(Enseignant::query(), $user);
        $classify = fn ($q, array $codes) => $q->whereHas('corps', fn ($c) => $c
            ->whereIn('code', $codes)->orWhereIn('libelle', $codes));
        $required = config('personnel.required_dossier_fields');

        return $this->context($user) + [
            'agent' => ['id' => $user->id, 'nom' => $user->nom_complet, 'profil' => $user->role->nom],
            'indicateurs' => [
                'total_agents' => (clone $query)->count(),
                'enseignants_fonctionnaires' => $classify(clone $query, config('personnel.corps_fonctionnaires'))->count(),
                'enseignants_non_fonctionnaires' => $classify(clone $query, config('personnel.corps_non_fonctionnaires'))->count(),
                'dossiers_actifs' => (clone $query)->where('est_actif', true)->where('statut', 'en_activite')->count(),
                'dossiers_incomplets' => (clone $query)->where(function ($q) use ($required) {
                    foreach ($required as $field) {
                        $q->orWhereNull($field);
                        if (in_array($field, ['matricule', 'nom', 'prenom'], true)) {
                            $q->orWhereRaw('TRIM('.$field.") = ''");
                        }
                    }
                })->count(),
            ],
            'agents_par_lieu_service' => (clone $query)->select('lieu_service_id')
                ->selectRaw('COUNT(*) AS total')->groupBy('lieu_service_id')
                ->with('lieuService:id,libelle')->get()->map(fn ($row) => [
                    'lieu_service_id' => $row->lieu_service_id,
                    'libelle' => $row->lieuService?->libelle,
                    'total' => $row->total,
                ]),
            'derniers_dossiers' => (clone $query)->orderByDesc('updated_at')->orderByDesc('id')
                ->limit(10)->get(['id', 'matricule', 'nom', 'prenom', 'created_at', 'updated_at']),
        ];
    }
}
