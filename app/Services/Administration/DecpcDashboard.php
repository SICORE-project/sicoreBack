<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\Piece_justificatives;
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
            'indemnites_par_type' => (clone $query)
                ->leftJoin('type_indemnites', 'type_indemnites.id', '=', 'indemnites.type_indemnite_id')
                ->select('type_indemnites.libelle')
                ->selectRaw('COUNT(*) AS total, SUM(indemnites.montant_total) AS montant')
                ->groupBy('indemnites.type_indemnite_id', 'type_indemnites.libelle')->get(),
            'derniers_dossiers' => (clone $query)
                ->leftJoin('type_indemnites', 'type_indemnites.id', '=', 'indemnites.type_indemnite_id')
                ->select('indemnites.*', 'type_indemnites.libelle AS type')
                ->orderByDesc('indemnites.updated_at')->orderByDesc('indemnites.id')->limit(10)->get(),
            // Mêmes permissions que les routes API des modules (convocations.read / pieces.read ou indemnites.read).
            'convocations' => $user->hasAnyPermission(['convocations.read', 'indemnites.read']) ? $this->convocations() : null,
            'pieces' => $user->hasAnyPermission(['pieces.read', 'indemnites.read']) ? $this->pieces() : null,
        ];
    }

    private function convocations(): array
    {
        $statuts = Convocations::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut');

        return [
            'total' => (int) $statuts->sum(),
            'brouillons' => (int) ($statuts['brouillon'] ?? 0),
            'emises' => (int) ($statuts['emise'] ?? 0),
            'envoyees' => (int) ($statuts['envoyee'] ?? 0),
            'cloturees' => (int) ($statuts['cloturee'] ?? 0),
            'dernieres' => Convocations::query()->select('id', 'objet', 'statut', 'date_emission')
                ->orderByDesc('date_emission')->orderByDesc('id')->limit(5)->get(),
        ];
    }

    private function pieces(): array
    {
        $statuts = Piece_justificatives::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut');

        return [
            'total' => (int) $statuts->sum(),
            'a_verifier' => (int) ($statuts['depose'] ?? 0),
            'validees' => (int) ($statuts['valide'] ?? 0),
            'rejetees' => (int) ($statuts['rejete'] ?? 0),
        ];
    }
}
