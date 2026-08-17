<?php

namespace App\Http\Controllers;

use App\Models\PayrollPayslip;
use App\Services\PayrollPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPageController extends Controller
{
    public function __construct(private readonly PayrollPageService $pages) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
        ]);

        return response()->json([
            'data' => $this->pages->page($slug, $validated['period_id'] ?? null),
        ]);
    }

    public function export(Request $request, string $slug): StreamedResponse
    {
        $validated = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
        ]);
        $page = $this->pages->page($slug, $validated['period_id'] ?? null);
        $filename = $slug.'-'.($page['period']['code'] ?? now()->format('Y-m')).'.csv';

        return response()->streamDownload(function () use ($page): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $page['columns'], ';');

            foreach ($page['rows'] as $row) {
                fputcsv($stream, array_map(function (mixed $cell): string {
                    if (! is_array($cell)) {
                        return (string) $cell;
                    }

                    return (string) ($cell['value'] ?? '');
                }, $row), ';');
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function payslip(PayrollPayslip $payslip): JsonResponse
    {
        $payslip->load([
            'period',
            'lines',
            'enseignant.user',
            'enseignant.corps',
            'enseignant.institutionFinanciere',
            'enseignant.etablissement.ief.ia',
        ]);

        return response()->json([
            'data' => [
                'id' => $payslip->id,
                'reference' => $payslip->reference,
                'period' => [
                    'id' => $payslip->period->id,
                    'code' => $payslip->period->code,
                    'label' => $payslip->period->label,
                ],
                'teacher' => [
                    'matricule' => $payslip->enseignant->matricule,
                    'name' => trim(
                        ($payslip->enseignant->user?->prenom ?? '').' '
                        .($payslip->enseignant->user?->nom ?? '')
                    ),
                    'corps' => $payslip->enseignant->corps?->libelle,
                    'bank' => $payslip->enseignant->institutionFinanciere?->nom,
                    'account_last_four' => $payslip->enseignant->numero_compte
                        ? mb_substr($payslip->enseignant->numero_compte, -4)
                        : null,
                    'academic_inspection' => $payslip->enseignant->etablissement?->ief?->ia?->libelle,
                    'education_inspection' => $payslip->enseignant->etablissement?->ief?->libelle,
                    'establishment' => $payslip->enseignant->etablissement?->libelle,
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
