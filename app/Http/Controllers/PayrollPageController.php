<?php

namespace App\Http\Controllers;

use App\Models\PayrollPayslip;
use App\Services\PayrollPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPageController extends Controller
{
    private const IA_PERMISSIONS = [
        'paie-montants-engages-banque' => 'paie.bulletins.read',
        'paie-edition-salaires-banque' => 'paie.bulletins.read',
        'paie-elements-saisie-dashboard' => 'paie.bulletins.read',
        'paie-recap-elements-corps' => 'paie.bulletins.read',
        'paie-cumul-enseignants-ief' => 'paie.bulletins.read',
        'paie-effectifs-corps' => 'paie.bulletins.read',
        'paie-non-generee' => 'paie.bulletins.read',
        'paie-edition-enseignants' => 'paie.bulletins.read',
        'paie-edition-fonctionnaires' => 'paie.bulletins.read',
        'paie-mutuelles-sante' => 'paie.bulletins.read',
        'paie-situation-affectations' => 'paie.bulletins.read',
        'paie-prime-scolaire' => 'paie.bulletins.read',
        'paie-reliquats' => 'paie.bulletins.read',
        'paie-double-flux' => 'paie.bulletins.read',
        'paie-directeurs-interim' => 'paie.bulletins.read',
        'paie-heures-supplementaires-interim' => 'paie.bulletins.read',
        'paie-bulletins' => 'paie.bulletins.read',
        'paie-travaux-periodiques' => 'paie.bulletins.read',
        'paie-etat-salaires' => 'paie.etat_salaires.read',
        'paie-cotisations-sociales' => 'paie.cotisations.read',
        'paie-recap-banque' => 'paie.recap_banque.read',
        'paie-generee-ief' => 'paie.effectifs_ief.read',
        'paie-sommes-percues' => 'paie.sommes_percues.read',
    ];

    private function page(Request $request, string $slug, array $filters): array
    {
        $user = $request->user();
        if (! $user?->hasRole('gestionnaire_ia')) {
            return $this->pages->page($slug, $filters['period_id'] ?? null, $filters);
        }
        abort_unless(isset(self::IA_PERMISSIONS[$slug]) && $user->hasPermission(self::IA_PERMISSIONS[$slug]), 403);
        $iaId = app(\App\Services\Administration\IaScope::class)->id($user);
        $page = $this->pages->forIa($iaId)->page($slug, $filters['period_id'] ?? null, array_merge($filters, ['ia_id' => $iaId]));
        $page['scope_ia_id'] = $iaId;
        $page['scope_label'] = \Illuminate\Support\Facades\DB::table('ias')->where('id', $iaId)->value('libelle');
        $page['notice'] = 'Périmètre : '.$page['scope_label'].'. '.$page['notice'];
        $page['actions'] = collect($page['actions'])->filter(fn ($action) => ($action['code'] ?? '') === 'export' && $user->hasPermission('paie.bulletins.export'))->values()->all();
        $page['report_catalog'] = collect($page['report_catalog'])->filter(fn ($report) => isset(self::IA_PERMISSIONS[$report['slug']]) && $user->hasPermission(self::IA_PERMISSIONS[$report['slug']]))->values()->all();
        if ($slug === 'paie-travaux-periodiques') {
            $page['stats'][0]['value'] = count($page['report_catalog']);
        }
        $page['rows'] = collect($page['rows'])->map(fn ($row) => collect($row)->map(function ($cell) {
            if (is_array($cell) && isset($cell['actions'])) {
                $cell['actions'] = array_values(array_filter($cell['actions'], fn ($action) => $action['code'] === 'view-payslip'));
            }
            return $cell;
        })->all())->all();
        return $page;
    }

    public function __construct(private readonly PayrollPageService $pages) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $validated = $this->validatedFilters($request, $slug);

        return response()->json([
            'data' => $this->page($request, $slug, $validated),
        ]);
    }

    public function export(Request $request, string $slug): StreamedResponse
    {
        $validated = $this->validatedFilters($request, $slug);
        abort_if($request->user()?->hasRole('gestionnaire_ia') && ! $request->user()->hasPermission('paie.bulletins.export'), 403);
        $page = $this->page($request, $slug, $validated);
        $filename = $slug.'-'.($page['period']['code'] ?? now()->format('Y-m')).'.csv';

        return response()->streamDownload(function () use ($page): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $page['columns'], ';', '"', '');

            foreach ($page['rows'] as $row) {
                fputcsv($stream, array_map(function (mixed $cell): string {
                    if (! is_array($cell)) {
                        $value = (string) $cell;
                        return preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value;
                    }

                    return (string) ($cell['value'] ?? '');
                }, $row), ';', '"', '');
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Valide les critères communs puis, uniquement pour l'état des salaires,
     * les options d'édition issues du formulaire métier historique.
     *
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request, string $slug): array
    {
        $rules = [
            'period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
        ];

        if ($slug === 'paie-etat-salaires') {
            $rules += [
                'academic_year_id' => ['nullable', 'integer'],
                'corps_id' => ['nullable', 'integer'],
                'ia_id' => ['nullable', 'integer'],
                'ief_id' => ['nullable', 'integer'],
                'matricule' => ['nullable', 'string', 'max:50'],
                'payment_place_id' => ['nullable', 'integer'],
                'training_center_id' => ['nullable', 'integer'],
                'tabaski_only' => ['nullable', 'boolean'],
                'with_signature' => ['nullable', 'boolean'],
                'without_service_done' => ['nullable', 'boolean'],
                'dage_signatory' => ['nullable', 'boolean'],
            ];
        }

        return $request->validate($rules);
    }

    public function payslip(PayrollPayslip $payslip): JsonResponse
    {
        if (request()->user()?->hasRole('gestionnaire_ia')) {
            abort_unless(app(\App\Services\Administration\IaScope::class)->payslips(request()->user())->where('p.id', $payslip->id)->exists(), 403);
        }
        $payslip->load([
            'period',
            'lines',
            'enseignant.user',
            'enseignant.corps',
            'enseignant.institutionFinanciere',
            'enseignant.etablissement.ief.ia',
        ]);
        $teacher = $payslip->enseignant;
        $ief = $teacher->etablissement?->ief
            ?? ($teacher->ief_id ? DB::table('iefs')->where('id', $teacher->ief_id)->first() : null);
        $iaId = $teacher->etablissement?->ief?->ia_id
            ?? $teacher->ia_id
            ?? $ief?->ia_id;
        $iaLabel = $teacher->etablissement?->ief?->ia?->libelle
            ?? ($iaId ? DB::table('ias')->where('id', $iaId)->value('libelle') : null);
        $corpsId = $teacher->getAttribute('corps_id')
            ?? $teacher->getAttribute('corps_enseignant_id');
        $corpsLabel = $teacher->corps?->libelle
            ?? ($corpsId ? DB::table('corps_enseignant')->where('id', $corpsId)->value('libelle') : null);
        $teacherName = trim(
            ($teacher->user?->prenom ?? $teacher->prenom ?? '').' '
            .($teacher->user?->nom ?? $teacher->nom ?? '')
        );

        return response()->json([
            'data' => [
                'id' => $payslip->id,
                'reference' => $payslip->reference,
                'period' => [
                    'id' => $payslip->period->id,
                    'code' => $payslip->period->code,
                    'label' => $payslip->period->label,
                    'month_label' => $payslip->period->label,
                    'month_number' => (int) $payslip->period->start_date->format('n'),
                    'year' => (int) $payslip->period->start_date->format('Y'),
                ],
                'teacher' => [
                    'matricule' => $teacher->matricule,
                    'name' => $teacherName,
                    'corps' => $corpsLabel,
                    'bank' => $teacher->institutionFinanciere?->nom,
                    'account_last_four' => $teacher->numero_compte
                        ? mb_substr($teacher->numero_compte, -4)
                        : null,
                    'academic_inspection' => $iaLabel,
                    'education_inspection' => $teacher->etablissement?->ief?->libelle ?? $ief?->libelle,
                    'establishment' => $teacher->etablissement?->libelle,
                ],
                'profile' => [
                    'engagement_type' => data_get($payslip->profile_snapshot, 'engagement_type'),
                    'engagement_label' => match (data_get($payslip->profile_snapshot, 'engagement_type')) {
                        'contractuel' => 'Professeur contractuel',
                        'vacataire' => 'Vacataire',
                        default => 'Profil historique',
                    },
                    'diploma' => data_get($payslip->profile_snapshot, 'diploma_label'),
                    'category' => data_get($payslip->profile_snapshot, 'category_level'),
                    'calculation_model' => data_get($payslip->profile_snapshot, 'calculation_model'),
                ],
                'gross_amount' => $payslip->gross_amount,
                'deduction_amount' => $payslip->deduction_amount,
                'employer_contribution_amount' => $payslip->employer_contribution_amount,
                'net_amount' => $payslip->net_amount,
                'payment_status' => $payslip->payment_status,
                'payment_reference' => $payslip->payment_reference,
                'paid_at' => $payslip->paid_at?->toIso8601String(),
                'edited_on' => now()->format('d/m/Y'),
                'lines' => $payslip->lines->map(fn ($line): array => [
                    'code' => $line->code,
                    'label' => $line->label,
                    'category' => $line->category,
                    'amount' => $line->amount,
                    'source' => $line->source,
                    'is_augmentation' => $line->source === 'salary_increase',
                ])->values(),
            ],
        ]);
    }
}
