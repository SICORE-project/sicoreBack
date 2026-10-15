<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;
use App\Services\Administration\Personnel\DrhDashboard;

class DecpcDashboard
{
    public function context(User $user): array
    {
        return array_replace(app(DrhDashboard::class)->context($user), [
            'dashboard_path' => '/decpc/dashboard',
            'dashboard_api' => '/api/decpc/dashboard',
            'perimetre' => app(DecpcScope::class)->describe($user),
        ]);
    }

    public function data(User $user): array
    {
        $scope = app(DecpcScope::class);
        $query = $scope->indemnites($user);
        if (! $user->hasPermission('indemnites.read')) {
            $query->whereRaw('1 = 0');
        }
        $teachers = $scope->apply(Enseignant::query(), $user);

        return $this->context($user) + [
            'indicateurs' => [
                'total_agents' => $user->hasPermission('enseignants.read') ? $teachers->count() : 0,
                'indemnites_en_attente' => (clone $query)->whereIn('statut', ['brouillon', 'calcule'])->count(),
                'indemnites_validees' => (clone $query)->where('statut', 'valide')->count(),
                'indemnites_rejetees' => (clone $query)->where('statut', 'rejete')->count(),
                'montant_indemnites_en_cours' => (float) (clone $query)->whereIn('statut', ['brouillon', 'calcule'])->sum('montant_total'),
            ],
            'indemnites_par_type' => (clone $query)->select('type_indemnite_id')
                ->selectRaw('COUNT(*) AS total, SUM(montant_total) AS montant_total')->groupBy('type_indemnite_id')->get(),
            'derniers_dossiers' => (clone $query)->orderByDesc('updated_at')->orderByDesc('id')->limit(10)->get(),
        ];
    }
}
