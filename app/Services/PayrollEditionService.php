<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Enseignant;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use App\Models\Personnel\Enseignant as PersonnelEnseignant;
use App\Services\Administration\IaScope;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Mises à jour des fiches par l'IA et validation de l'Édition.
 *
 * Le gestionnaire d'une IA modifie les fiches de ses enseignants dans
 * Gestion du personnel > Enseignants : la fiche change tout de suite et la
 * modification est journalisée. Dans Paie > États de présence > Édition,
 * « Valider l'Édition » recalcule les bulletins de l'IA pour le mois et
 * débloque son État des salaires. Un mois validé l'est définitivement : une
 * modification postérieure est prise en compte au mois suivant, à la
 * validation de celui-ci (les bulletins sont recalculés avec la fiche à jour).
 */
class PayrollEditionService
{
    /** Informations suivies dans le journal des modifications (avant / après). */
    private const TRACKED = [
        'situation_matrimoniale' => 'Situation matrimoniale',
        'nombre_enfants' => 'Nombre d’enfants',
        'nombre_femmes' => 'Nombre d’épouses',
        'conjoint_travaille' => 'Conjoint salarié',
        'nombre_parts_fiscales' => 'Parts fiscales',
        'date_prise_service' => 'Prise de service',
        'date_recrutement' => 'Recrutement',
        'date_fin_contrat' => 'Fin de contrat',
        'ief_id' => 'IEF',
        'lieu_service_id' => 'Lieu de service',
        'diplome_id' => 'Diplôme',
        'categorie_id' => 'Catégorie',
        'salaire_brut' => 'Salaire brut',
        'rib' => 'RIB',
        'institut_financier_id' => 'Banque',
        'syndicat_id' => 'Syndicat',
        'mutuelle_id' => 'Mutuelle',
    ];

    /** Enregistre la fiche (Gestion du personnel > Enseignants) et journalise ce qui a changé. */
    public function updateTeacher(User $user, int $teacherId, array $data): array
    {
        $ia = app(IaScope::class)->id($user);
        $teacher = $this->iaTeacher($ia, $teacherId);

        return DB::transaction(function () use ($user, $ia, $teacher, $data) {
            $before = $this->snapshot($teacher->id);
            $mutuelle = $data['mutuelle'] ?? null;
            unset($data['mutuelle']);
            app(EnseignantService::class)->update($teacher, $data, $user->id);
            if (is_array($mutuelle)) {
                $this->setMutuelle($teacher->id, $mutuelle['mutuelle_id'] ?? null);
            }
            $after = $this->snapshot($teacher->id);

            $changes = [];
            foreach (self::TRACKED as $field => $label) {
                if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
                    $changes[$field] = ['label' => $label, 'avant' => $before[$field] ?? null, 'apres' => $after[$field] ?? null];
                }
            }
            if ($changes !== []) {
                DB::table('payroll_edition_changes')->insert([
                    'payroll_period_id' => null, 'ia_id' => $ia, 'enseignant_id' => $teacher->id,
                    'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE), 'changed_by' => $user->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return ['changes' => $changes];
        });
    }

    /** Valide l'Édition de l'IA pour le mois : recalcul des bulletins de l'IA. */
    public function validate(User $user, int $periodId, Request $request, ?string $key = null): array
    {
        $ia = app(IaScope::class)->id($user);

        return DB::transaction(function () use ($user, $ia, $periodId, $request, $key) {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($periodId);
            if ($period->status === PayrollPeriod::STATUS_CLOSED) {
                throw new ConflictHttpException('La période '.$period->code.' est clôturée : son Édition ne peut plus être validée.');
            }
            // Une seule validation par IA et par mois.
            if (DB::table('payroll_edition_validations')->where('payroll_period_id', $period->id)->where('ia_id', $ia)->lockForUpdate()->exists()) {
                throw new ConflictHttpException('L’Édition de '.$period->label.' est déjà validée pour votre IA. Les mises à jour postérieures seront prises en compte le mois suivant.');
            }
            $ids = Enseignant::query()->contractuelOuVacataire()->eligiblePourPeriode($period)
                ->where('enseignants.ia_id', $ia)->orderBy('enseignants.id')->pluck('enseignants.id')->all();

            // Bulletins de l'IA qui n'ont plus lieu d'être (fin de contrat, mutation…).
            $stale = PayrollPayslip::query()->where('payroll_period_id', $period->id)->where('ia_id', $ia)
                ->whereNotIn('enseignant_id', $ids)->get();
            if ($stale->contains('payment_status', 'paid')) {
                throw new ConflictHttpException('Un bulletin déjà payé ne peut pas être supprimé : '.$stale->where('payment_status', 'paid')->pluck('reference')->implode(', ').'.');
            }
            if ($stale->isNotEmpty() && $ids === []) {
                throw ValidationException::withMessages(['payroll_period_id' => 'Plus aucun enseignant de votre IA n’est en service sur ce mois, mais des bulletins existent encore. Contactez la cellule paie.']);
            }
            PayrollPayslip::query()->whereIn('id', $stale->pluck('id'))->delete();

            if ($ids !== []) {
                app(PayrollTabaskiSchedule::class)->materialize($period, $ids, $user);
                app(PayrollCalculationService::class)->calculate($period, $user, $ids);
            }

            $slips = PayrollPayslip::query()->where('payroll_period_id', $period->id)->where('ia_id', $ia);
            $values = [
                'validated_by' => $user->id, 'validated_at' => now(),
                'payslip_count' => (clone $slips)->count(), 'total_net' => (float) (clone $slips)->sum('net_amount'),
                'updated_at' => now(),
            ];
            DB::table('payroll_edition_validations')->updateOrInsert(
                ['payroll_period_id' => $period->id, 'ia_id' => $ia],
                $values + ['created_at' => now()],
            );

            app(PayrollAuditService::class)->log('edition.validated', $period, null,
                ['ia_id' => $ia, 'payslip_count' => $values['payslip_count'], 'total_net' => $values['total_net'], 'removed' => $stale->pluck('reference')->all()],
                $user, $request, $key);

            return $this->status($period->fresh(), $ia) + ['removed' => $stale->pluck('reference')->all()];
        });
    }

    /**
     * État de chaque mois de l'IA, pour les boutons « Générer » / « Consulter ».
     * Clés « {annee_academique_id}-{mois} » et « p{payroll_period_id} ».
     *
     * @return array<string, array{period_id: int, status: string, statement_generated: bool}>
     */
    public function monthStates(int $ia): array
    {
        if (! Schema::hasTable('payroll_edition_validations')) {
            return [];
        }
        $states = [];
        $periods = PayrollPeriod::query()->whereIn('id',
            DB::table('payroll_edition_validations')->where('ia_id', $ia)->select('payroll_period_id'))->get();
        foreach ($periods as $period) {
            $status = $this->status($period, $ia);
            $state = [
                'period_id' => $period->id,
                'status' => $status['status'],
                'statement_generated' => $status['status'] === 'validee' && $status['statement_generated_at'] !== null,
            ];
            $states['p'.$period->id] = $state;
            if ($period->annee_academique_id) {
                $states[$period->annee_academique_id.'-'.(int) $period->start_date->format('n')] = $state;
            }
        }

        return $states;
    }

    /** Enregistre la première génération de l'État des salaires de l'IA pour un mois validé. */
    public function markStatementGenerated(User $user, int $periodId): array
    {
        $ia = app(IaScope::class)->id($user);
        $period = PayrollPeriod::query()->findOrFail($periodId);
        if ($this->status($period, $ia)['status'] === 'validee') {
            DB::table('payroll_edition_validations')->where('payroll_period_id', $period->id)->where('ia_id', $ia)
                ->whereNull('statement_generated_at')
                ->update(['statement_generated_at' => now(), 'statement_generated_by' => $user->id, 'updated_at' => now()]);
        }

        return $this->status($period, $ia);
    }

    /** @return array<int, int> IA dont l'Édition du mois est validée et sans modification depuis. */
    public function validatedIas(PayrollPeriod $period): array
    {
        if (! Schema::hasTable('payroll_edition_validations')) {
            return [];
        }

        return DB::table('payroll_edition_validations')->where('payroll_period_id', $period->id)
            ->pluck('ia_id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /** @return array<string, mixed> */
    public function status(PayrollPeriod $period, int $ia): array
    {
        if (! Schema::hasTable('payroll_edition_validations')) {
            return ['status' => 'jamais', 'pending_count' => 0, 'pending_teacher_ids' => []];
        }
        $validation = DB::table('payroll_edition_validations as v')->leftJoin('users as u', 'u.id', '=', 'v.validated_by')
            ->where('v.payroll_period_id', $period->id)->where('v.ia_id', $ia)
            ->first(['v.validated_at', 'v.payslip_count', 'v.total_net', 'v.statement_generated_at', 'u.prenom', 'u.nom']);
        // Mises à jour en attente : depuis la dernière validation de l'IA (tous mois) si le mois
        // n'est pas validé ; après sa validation sinon (elles iront au mois suivant).
        $since = $validation?->validated_at ?? DB::table('payroll_edition_validations')->where('ia_id', $ia)->max('validated_at');
        $pending = $this->changesSince($ia, $since)->distinct()->pluck('enseignant_id')->map(fn ($id) => (int) $id)->all();

        return [
            'status' => $validation ? 'validee' : 'jamais',
            'pending_since' => $since,
            'validated_at' => $validation?->validated_at,
            'validated_by' => $validation ? trim(($validation->prenom ?? '').' '.($validation->nom ?? '')) : null,
            'payslip_count' => (int) ($validation->payslip_count ?? 0),
            'total_net' => (float) ($validation->total_net ?? 0),
            'statement_generated_at' => $validation->statement_generated_at ?? null,
            'pending_count' => count($pending),
            'pending_teacher_ids' => $pending,
        ];
    }

    /** Données du bandeau de la page Édition (état du mois et modifications depuis la validation). */
    public function pageData(?PayrollPeriod $period, ?int $ia, bool $canValidate): ?array
    {
        if (! $period || ! $ia || ! Schema::hasTable('payroll_edition_validations')) {
            return null;
        }
        $status = $this->status($period, $ia);

        return [
            'period_id' => $period->id,
            'period_label' => $period->label,
            'ia_id' => $ia,
            'can_validate' => $canValidate && $period->status !== PayrollPeriod::STATUS_CLOSED,
            'changes' => $this->changesSince($ia, $status['pending_since'])
                ->join('enseignants as e', 'e.id', '=', 'payroll_edition_changes.enseignant_id')
                ->orderByDesc('payroll_edition_changes.id')->limit(100)
                ->get(['payroll_edition_changes.enseignant_id', 'e.matricule', 'e.prenom', 'e.nom', 'payroll_edition_changes.changes', 'payroll_edition_changes.created_at'])
                ->map(fn ($c) => ['enseignant_id' => $c->enseignant_id, 'matricule' => $c->matricule,
                    'name' => trim($c->prenom.' '.$c->nom), 'at' => $c->created_at, 'changes' => json_decode($c->changes, true)])->all(),
        ] + $status;
    }

    /** Valeurs actuelles et listes du formulaire « Modifier » (Gestion du personnel > Enseignants). */
    public function teacherForm(User $user, int $teacherId): array
    {
        $ia = app(IaScope::class)->id($user);
        $t = $this->iaTeacher($ia, $teacherId);
        $account = DB::table('comptes_bancaires_enseignants')->where('enseignant_id', $t->id)->where('est_actif', true)
            ->orderByDesc('est_principal')->orderByDesc('id')->first();
        $date = fn ($value) => $value ? substr((string) $value, 0, 10) : null;
        $list = fn (string $table, string $label = 'libelle', ?\Closure $scope = null) => Schema::hasTable($table)
            ? DB::table($table)->when(Schema::hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->when($scope, $scope)->orderBy($label)->get()
            : collect();
        $raw = DB::table('enseignants')->where('id', $t->id)->first();

        return [
            'id' => $t->id,
            'matricule' => $t->matricule,
            'name' => trim(($t->prenom ?? '').' '.($t->nom ?? '')),
            'values' => [
                'situation_matrimoniale' => $raw->situation_matrimoniale,
                'nombre_enfants' => $raw->nombre_enfants,
                'nombre_femmes' => $raw->nombre_femmes,
                'conjoint_travaille' => (bool) $raw->conjoint_travaille,
                'date_prise_service' => $date($raw->date_prise_service),
                'date_recrutement' => $date($raw->date_recrutement),
                'date_fin_contrat' => $date($raw->date_fin_contrat),
                'ief_id' => $raw->ief_id,
                'lieu_service_id' => $raw->lieu_service_id,
                'diplome_id' => $raw->diplome_id,
                'categorie_id' => $raw->categorie_id,
                'syndicat_id' => DB::table('enseignant_syndicat')->where('enseignant_id', $t->id)->where('est_actif', true)->orderByDesc('id')->value('syndicat_id'),
                'mutuelle_id' => Schema::hasTable('enseignant_mutuelle')
                    ? DB::table('enseignant_mutuelle')->where('enseignant_id', $t->id)->where('est_actif', true)->orderByDesc('id')->value('mutuelle_id') : null,
                'institut_financier_id' => $account?->institut_financier_id,
                'code_banque' => $account?->code_banque,
                'code_guichet' => $account?->code_guichet,
                'numero_compte' => $account?->numero_compte,
                'cle_rib' => $account?->cle_rib,
            ],
            'options' => [
                'iefs' => $list('iefs', 'libelle', fn ($q) => $q->where('ia_id', $ia))->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle])->values(),
                'lieux_service' => $list('lieu_de_services', 'libelle', fn ($q) => $q->where('ia_id', $ia))->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle, 'ief_id' => $r->ief_id])->values(),
                'diplomes' => $list('diplomes')->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle])->values(),
                'categories' => $list('categories')->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle])->values(),
                'syndicats' => $list('syndicats', 'libelle', fn ($q) => $q->where('est_actif', true))->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle])->values(),
                'mutuelles' => $list('mutuelles', 'nom', fn ($q) => $q->where('est_actif', true))->map(fn ($r) => ['value' => $r->id, 'label' => $r->nom])->values(),
                'banques' => $list('instituts_financieres')->map(fn ($r) => ['value' => $r->id, 'label' => $r->libelle])->values(),
                'situations' => [
                    ['value' => 'celibataire', 'label' => 'Célibataire'], ['value' => 'marie', 'label' => 'Marié(e)'],
                    ['value' => 'veuf', 'label' => 'Veuf / veuve'], ['value' => 'divorce', 'label' => 'Divorcé(e)'],
                ],
            ],
        ];
    }

    /** Modifications de fiches de l'IA postérieures à une date (toutes si aucune date). */
    private function changesSince(int $ia, mixed $since)
    {
        return DB::table('payroll_edition_changes')->where('payroll_edition_changes.ia_id', $ia)
            ->when($since !== null, fn ($q) => $q->where('payroll_edition_changes.created_at', '>', $since));
    }

    private function iaTeacher(int $ia, int $teacherId): PersonnelEnseignant
    {
        $teacher = PersonnelEnseignant::query()->where('id', $teacherId)->where('ia_id', $ia)->whereNull('deleted_at')->first();
        abort_unless($teacher, 404, 'Enseignant introuvable dans votre IA.');

        return $teacher;
    }

    /** @return array<string, mixed> */
    private function snapshot(int $teacherId): array
    {
        $t = DB::table('enseignants')->where('id', $teacherId)->first();
        $account = DB::table('comptes_bancaires_enseignants')->where('enseignant_id', $teacherId)->where('est_actif', true)
            ->orderByDesc('est_principal')->orderByDesc('id')->first();
        $values = [];
        foreach (array_keys(self::TRACKED) as $field) {
            if (property_exists($t, $field)) $values[$field] = $t->{$field};
        }
        foreach (['date_prise_service', 'date_recrutement', 'date_fin_contrat'] as $field) {
            if (! empty($values[$field])) $values[$field] = substr((string) $values[$field], 0, 10);
        }
        $values['rib'] = $account?->rib;
        $values['institut_financier_id'] = $account?->institut_financier_id;
        $values['syndicat_id'] = DB::table('enseignant_syndicat')->where('enseignant_id', $teacherId)->where('est_actif', true)->orderByDesc('id')->value('syndicat_id');
        $values['mutuelle_id'] = Schema::hasTable('enseignant_mutuelle')
            ? DB::table('enseignant_mutuelle')->where('enseignant_id', $teacherId)->where('est_actif', true)->orderByDesc('id')->value('mutuelle_id')
            : null;

        return $values;
    }

    private function setMutuelle(int $teacherId, mixed $mutuelleId): void
    {
        $now = now();
        DB::table('enseignant_mutuelle')->where('enseignant_id', $teacherId)->where('est_actif', true)
            ->when($mutuelleId, fn ($q) => $q->where('mutuelle_id', '<>', $mutuelleId))
            ->update(['est_actif' => false, 'date_resiliation' => $now->toDateString(), 'updated_at' => $now]);
        if (! $mutuelleId) return;
        $exists = DB::table('enseignant_mutuelle')->where('enseignant_id', $teacherId)->where('mutuelle_id', $mutuelleId)->orderByDesc('id')->first();
        if ($exists) {
            DB::table('enseignant_mutuelle')->where('id', $exists->id)->update(['est_actif' => true, 'date_resiliation' => null, 'updated_at' => $now]);
        } else {
            DB::table('enseignant_mutuelle')->insert(['enseignant_id' => $teacherId, 'mutuelle_id' => $mutuelleId,
                'date_adhesion' => $now->toDateString(), 'est_actif' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
