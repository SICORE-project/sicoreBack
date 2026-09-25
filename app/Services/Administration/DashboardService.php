<?php

namespace App\Services\Administration;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\Personnel\Enseignant;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private OrganizationalScope $scope) {}

    public function forUser(User $user): array
    {
        $user->loadMissing(['role.permissions', 'role.typeRole', 'lieuService']);
        abort_unless($user->statut === 'actif' && $user->role?->est_actif, 403, 'Votre compte ou votre profil est inactif.');
        $role = $user->role->slug;
        $admin = in_array($role, ['super_admin', 'admin'], true);
        $titles = [
            'super_admin' => 'Pilotage de la plateforme', 'admin' => 'Administration des accès',
            'gestionnaire_ia' => 'Suivi de votre inspection académique',
            'gestionnaire_ief' => 'Suivi de votre IEF', 'drh' => 'Ressources humaines',
            'gestionnaire_paie' => 'Suivi de la paie', 'gestionnaire_budget' => 'Suivi des crédits',
            'enseignant' => 'Mon espace enseignant', 'consultant' => 'Consultation et suivi',
            'auditeur' => 'Contrôle de la paie', 'parametreur' => 'Paramétrage de la plateforme',
        ];
        $data = [
            'title' => $titles[$role] ?? 'Mon tableau de bord',
            'role' => $user->role->nom,
            'name' => trim($user->prenom.' '.$user->nom),
            'scope' => $role === 'enseignant' ? 'Votre dossier personnel' : ($admin ? 'Administration centrale' : ($user->lieuService?->libelle ?? 'Aucune structure attribuée')),
            'updated_at' => now()->toIso8601String(),
            'cards' => [], 'actions' => [], 'notices' => [], 'sections' => [],
        ];
        $can = fn (string $permission): bool => $role === 'super_admin'
            || $user->role->permissions->contains(fn ($item) => $item->slug === $permission && $item->est_actif);

        // This branch deliberately precedes all collective permissions.
        if ($role === 'enseignant') {
            return $this->personal($data, $user, $can);
        }
        if ($role === 'parametreur') {
            $data['scope'] = 'Référentiels autorisés';
            if ($can('parametrage.corps.read')) {
                $data['cards'][] = $this->card('Corps enseignants', \App\Models\Parametrage\CorpsEnseignant::count(), 'fa-layer-group', group: 'Référentiels');
                $data['actions'][] = $this->action('Corps enseignants', 'parametres.corps.index', 'fa-layer-group');
            }
            if ($can('parametrage.categories.read')) {
                $data['cards'][] = $this->card('Catégories', \App\Models\Parametrage\Categorie::count(), 'fa-tags', group: 'Référentiels');
                $data['actions'][] = $this->action('Catégories', 'parametres.categories.index', 'fa-tags');
            }
            if ($data['cards'] === []) $data['notices'][] = 'Aucun référentiel accessible avec vos autorisations actuelles.';
            return $data;
        }
        if (! $admin && (! $user->lieuService || ! $user->lieuService->est_actif)) {
            $data['notices'][] = 'Demandez à votre administrateur de vous rattacher à une structure active pour consulter vos indicateurs.';
            return $data;
        }
        $type = strtoupper($user->lieuService?->type ?? '');
        if (($role === 'gestionnaire_ia' && ($type !== 'IA' || ! $user->lieuService?->ia_id))
            || ($role === 'gestionnaire_ief' && ($type !== 'IEF' || ! $user->lieuService?->ief_id))) {
            $data['notices'][] = 'Votre rattachement territorial doit être complété par un administrateur.';
            return $data;
        }
        $teachers = $admin ? Enseignant::query() : $this->scope->apply(Enseignant::query(), $user);
        if ($role === 'gestionnaire_ia') $teachers->where('ia_id', $user->lieuService->ia_id);
        if ($role === 'gestionnaire_ief') $teachers->where('ief_id', $user->lieuService->ief_id);

        if ($admin) {
            if ($can('administration.users.read')) {
                $data['cards'][] = $this->card('Comptes utilisateurs', User::count(), 'fa-users', group: 'Administration');
                $data['cards'][] = $this->card('Comptes actifs', User::where('statut', 'actif')->count(), 'fa-user-check', group: 'Administration');
                $inactive = User::whereIn('statut', ['inactif', 'bloque'])->count();
                $data['cards'][] = $this->card('Comptes inactifs ou bloqués', $inactive, 'fa-user-lock', group: 'Administration');
                if ($inactive) $data['notices'][] = "$inactive compte(s) inactif(s) ou bloqué(s) à examiner.";
                $data['actions'][] = $this->action('Gérer les utilisateurs', 'utilisateurs.index', 'fa-users-gear');
            }
            if ($can('administration.roles.read')) {
                $data['cards'][] = $this->card('Profils actifs', Role::where('est_actif', true)->count(), 'fa-id-badge', group: 'Administration');
                $data['actions'][] = $this->action('Consulter les profils', 'utilisateurs.profils-roles', 'fa-id-badge');
            }
            if ($can('administration.users.create')) $data['actions'][] = $this->action('Créer un compte enseignant', 'utilisateurs.teacher.create', 'fa-user-plus');
        }
        if ($can('enseignants.read') && ! in_array($role, ['gestionnaire_paie', 'gestionnaire_budget', 'auditeur'], true)) {
            $data['cards'][] = $this->card('Total des enseignants', (clone $teachers)->count(), 'fa-chalkboard-user', group: 'Personnel');
            $data['cards'][] = $this->card('Enseignants en activité', (clone $teachers)->where('statut', 'en_activite')->count(), 'fa-user-check', group: 'Personnel');
            $withoutEmail = (clone $teachers)->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))->count();
            $data['cards'][] = $this->card('Enseignants sans e-mail', $withoutEmail, 'fa-envelope', group: 'Personnel');
            if ($withoutEmail) $data['notices'][] = "$withoutEmail enseignant(s) sans adresse e-mail. Coordonnées à compléter.";
            $data['actions'][] = $this->action('Consulter les enseignants', 'enseignants.index', 'fa-chalkboard-user');
            $data['sections'][] = [
                'title' => 'Derniers enseignants ajoutés', 'columns' => ['Matricule', 'Enseignant', 'Statut'],
                'rows' => (clone $teachers)->latest('created_at')->orderByDesc('id')->limit(5)->get()->map(fn ($t) => [$t->matricule, $t->prenom.' '.$t->nom, $t->statut_libelle])->all(),
            ];
        }
        if ($can('paie.bulletins.read') && in_array($role, ['super_admin', 'admin', 'gestionnaire_paie', 'auditeur', 'consultant'], true)) {
            $period = PayrollPeriod::orderByDesc('start_date')->first();
            $payslips = PayrollPayslip::whereIn('enseignant_id', (clone $teachers)->select('enseignants.id'));
            if ($period) $payslips->where('payroll_period_id', $period->id);
            else $payslips->whereRaw('1 = 0');
            $data['cards'][] = $this->card('Bulletins de la dernière période', (clone $payslips)->count(), 'fa-file-invoice', group: 'Paie');
            $data['cards'][] = $this->card('Paiements en attente', (clone $payslips)->where('payment_status', 'pending')->count(), 'fa-hourglass-half', group: 'Paie');
            $data['cards'][] = $this->card('Paiements rejetés', (clone $payslips)->where('payment_status', 'rejected')->count(), 'fa-circle-exclamation', group: 'Paie');
            $data['cards'][] = $this->card('Montant net de la période', (clone $payslips)->sum('net_amount'), 'fa-money-bill-wave', 'FCFA', group: 'Paie');
            $data['notices'][] = $period ? 'Indicateurs de paie pour la période '.$period->code.'.' : 'Aucune période de paie disponible.';
            // Existing payroll screens authorize these roles and token abilities only.
            // Regional accounts stay on scoped dashboard data rather than a global payroll screen.
            if ($admin || in_array(strtoupper($user->lieuService?->type ?? ''), ['DAGE', 'DRH', 'DECPC'], true)) {
                if (in_array($role, config('payroll.read_roles', []), true) && $user->tokenCan('payroll:read')) {
                    $data['actions'][] = $this->action('Consulter les bulletins', 'paie.bulletins', 'fa-file-invoice');
                    $data['actions'][] = $this->action('État des salaires', 'paie.etat-salaires', 'fa-list-check');
                }
            }
        }
        if ($can('budget.read') && in_array($role, ['super_admin', 'gestionnaire_budget', 'consultant'], true)) {
            $credits = DB::table('delegation_credits')->whereIn('enseignant_id', (clone $teachers)->select('enseignants.id'));
            $data['cards'][] = $this->card('Délégations enregistrées', (clone $credits)->count(), 'fa-file-signature', group: 'Crédits');
            $data['cards'][] = $this->card('Crédits alloués', (clone $credits)->sum('montant_alouer'), 'fa-coins', 'FCFA', group: 'Crédits');
            $data['sections'][] = [
                'title' => 'Dernières délégations', 'columns' => ['Référence', 'Année académique', 'Montant alloué (FCFA)'],
                'rows' => (clone $credits)->orderByDesc('date_enregistrement')->orderByDesc('id')->limit(5)->get()->map(fn ($c) => [$c->reference_lettre, $c->annee_academique, number_format((float) $c->montant_alouer, 0, ',', ' ')])->all(),
            ];
        }
        if ($data['cards'] === []) $data['notices'][] = 'Aucun indicateur disponible pour vos autorisations actuelles.';
        if ($role === 'super_admin') $data = $this->superAdmin($data);
        return $data;
    }

    private function superAdmin(array $data): array
    {
        $data['layout'] = 'super-admin';
        $active = User::where('statut', 'actif')->where(fn ($q) => $q->whereNull('enseignant_id')->orWhereNotNull('password_changed_at'))->count();
        $pending = User::where('statut', 'actif')->whereNotNull('enseignant_id')->whereNull('password_changed_at')->count();
        foreach ($data['cards'] as &$card) {
            if ($card['label'] === 'Comptes actifs') $card['value'] = $active;
        }
        unset($card);
        $history = [];
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = $start->copy()->addMonth();
            $history[] = ['label' => $start->format('m/Y'),
                'teachers' => Enseignant::where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
                'users' => User::where('created_at', '>=', $start)->where('created_at', '<', $end)->count()];
        }
        $series = fn ($items) => collect($items)->map(fn ($value, $label) => ['label' => $label, 'value' => (int) $value])->values()->all();
        $period = PayrollPeriod::orderByDesc('start_date')->first();
        $payments = $period ? PayrollPayslip::where('payroll_period_id', $period->id)->selectRaw('payment_status, COUNT(*) as total')->groupBy('payment_status')->pluck('total', 'payment_status') : collect();
        $corps = \App\Models\Parametrage\CorpsEnseignant::query()->orderBy('id')->withCount([
            'enseignants',
            'enseignants as accounts_count' => fn ($query) => $query->whereHas('user'),
        ])->get();
        $teachersByCorps = $corps->map(fn ($item) => ['label' => $item->libelle, 'value' => $item->enseignants_count])->all();
        $accountsByCorps = $corps->map(fn ($item) => ['label' => $item->libelle, 'value' => $item->accounts_count])->all();
        $unassigned = Enseignant::where(fn ($query) => $query->whereNull('corps_id')->orWhereNotIn('corps_id', $corps->modelKeys()));
        if ((clone $unassigned)->exists()) {
            $teachersByCorps[] = ['label' => 'Corps non renseigné', 'value' => (clone $unassigned)->count()];
            $accountsByCorps[] = ['label' => 'Corps non renseigné', 'value' => (clone $unassigned)->whereHas('user')->count()];
        }
        $data['analytics'] = [
            'history' => $history,
            'distributions' => [
                ['title' => 'État des comptes', 'subtitle' => 'Activation et accès à la plateforme', 'items' => $series(['Comptes actifs' => $active, 'À vérifier' => $pending, 'Suspendus ou bloqués' => User::where('statut', '!=', 'actif')->count()])],
                ['title' => 'Comptes enseignants par corps', 'subtitle' => 'Enseignants disposant d’un compte · tous statuts', 'items' => $accountsByCorps],
                ['title' => 'Enseignants par corps', 'subtitle' => 'Répartition des enseignants enregistrés', 'items' => $teachersByCorps],
                ['title' => 'Paiements', 'subtitle' => $period ? 'Période '.$period->code : 'Aucune période de paie', 'items' => $series($payments->mapWithKeys(fn ($count, $status) => [(['paid' => 'Payés', 'pending' => 'En attente', 'rejected' => 'Rejetés'][$status] ?? $status) => $count]))],
            ],
        ];
        return $data;
    }

    private function personal(array $data, User $user, callable $can): array
    {
        $data['layout'] = 'teacher';
        $data['charts'] = null;
        $teacher = $user->enseignant_id ? Enseignant::find($user->enseignant_id) : null;
        if (! $teacher) {
            $data['notices'][] = 'Votre compte n’est pas encore lié à un dossier enseignant disponible. Contactez votre gestionnaire.';
            return $data;
        }
        $data['sections'][] = ['title' => 'Mon dossier', 'columns' => ['Information', 'Valeur'], 'rows' => [
            ['Matricule', $teacher->matricule], ['Nom complet', $teacher->prenom.' '.$teacher->nom],
            ['Statut', $teacher->statut_libelle], ['E-mail de connexion', $user->email],
        ]];
        if ($can('paie.bulletins.read')) {
            $slips = PayrollPayslip::where('enseignant_id', $teacher->id)
                ->whereHas('run', fn ($q) => $q->whereIn('status', ['validated', 'closed']));
            $data['cards'][] = $this->card('Mes bulletins validés', (clone $slips)->count(), 'fa-file-invoice', group: 'Mon suivi');
            $data['cards'][] = $this->card('Mes paiements effectués', (clone $slips)->where('payment_status', 'paid')->count(), 'fa-circle-check', group: 'Mon suivi');
            $data['cards'][] = $this->card('Paiements en attente', (clone $slips)->where('payment_status', 'pending')->count(), 'fa-clock', group: 'Mon suivi');
            $recent = (clone $slips)->with('period')
                ->orderByDesc(PayrollPeriod::select('start_date')->whereColumn('payroll_periods.id', 'payroll_payslips.payroll_period_id')->limit(1))
                ->orderByDesc('id')->limit(12)->get();
            $data['cards'][] = $this->card('Dernier montant net', $recent->first()?->net_amount, 'fa-wallet', 'FCFA', group: 'Mon suivi');
            $counts = (clone $slips)->selectRaw('payment_status, COUNT(*) as total')->groupBy('payment_status')->pluck('total', 'payment_status');
            $data['charts'] = [
                'net_history' => $recent->reverse()->values()->map(fn ($p) => [
                    'period' => $p->period?->code ?? $p->reference,
                    'amount' => (float) $p->net_amount,
                ])->all(),
                'payments' => [
                    ['label' => 'Payés', 'value' => (int) ($counts['paid'] ?? 0), 'color' => '#14866d'],
                    ['label' => 'En attente', 'value' => (int) ($counts['pending'] ?? 0), 'color' => '#e8aa42'],
                    ['label' => 'Rejetés', 'value' => (int) ($counts['rejected'] ?? 0), 'color' => '#dd6475'],
                ],
            ];
            $latest = $recent->take(5);
            $statuses = ['pending' => 'En attente', 'paid' => 'Payé', 'rejected' => 'Rejeté'];
            $data['sections'][] = ['title' => 'Mes derniers bulletins', 'columns' => ['Période', 'Référence', 'Net (FCFA)', 'Paiement'],
                'rows' => $latest->map(fn ($p) => [$p->period?->code, $p->reference, number_format((float) $p->net_amount, 0, ',', ' '), $statuses[$p->payment_status] ?? $p->payment_status])->all()];
        }
        return $data;
    }

    private function card(string $label, mixed $value, string $icon, ?string $unit = null, string $group = 'Votre activité'): array
    {
        return compact('label', 'value', 'icon', 'unit', 'group');
    }

    private function action(string $label, string $route, string $icon): array
    {
        return compact('label', 'route', 'icon');
    }
}
