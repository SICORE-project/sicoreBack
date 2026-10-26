<?php

namespace App\Services;

use App\Models\PayrollPayslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only adapter between payroll lines and the administration's rubric catalogue. */
class PayrollPayslipPresentationService
{
    /** Capture display settings only; amounts, categories and calculation sources stay untouched. */
    public function snapshot(array $lines): array
    {
        $codes = collect($lines)->pluck('code')->flatMap(fn ($code) => [$code, $this->reference($code)[0] ?? $code])->unique()->all();
        $rubrics = Schema::hasTable('rubrique_paies')
            ? DB::table('rubrique_paies')->whereIn('code', $codes)
                ->get(['id', 'code', 'libelle', 'est_afficher_bulletin'])->keyBy('code')
            : collect();

        $settings = [];
        foreach ($lines as $line) {
            $reference = $this->reference($line['code']);
            $rubric = $rubrics->get($line['code']) ?? $rubrics->get($reference[0] ?? $line['code']);
            $settings[$line['code']] = [
                'rubric_id' => $rubric?->id,
                'label' => $rubric && trim((string) $rubric->libelle) !== ''
                    ? $rubric->libelle : ($reference[1] ?? $line['label']),
                'visible' => $rubric ? (bool) $rubric->est_afficher_bulletin : true,
            ];
        }

        return $settings;
    }

    public function present(PayrollPayslip $payslip): array
    {
        $stored = data_get($payslip->profile_snapshot, 'bulletin_display');
        $settings = is_array($stored)
            ? $stored
            : $this->snapshot($payslip->lines->toArray());
        $hidden = 0;
        $matched = 0;
        $lines = $payslip->lines->map(function ($line) use ($settings, &$hidden, &$matched): ?array {
            $setting = $settings[$line->code] ?? [];
            if (($setting['rubric_id'] ?? null) !== null) {
                $matched++;
            }
            if (($setting['visible'] ?? true) === false) {
                $hidden++;
                return null;
            }

            return [
                'code' => $line->code,
                'display_code' => $this->reference($line->code)[0] ?? $line->code,
                'label' => ($setting['rubric_id'] ?? null) !== null
                    ? ($setting['label'] ?? $line->label)
                    : ($this->reference($line->code)[1] ?? $setting['label'] ?? $line->label),
                'category' => $line->category,
                'amount' => $line->amount,
                'source' => $line->source,
                'is_augmentation' => $line->source === 'salary_increase',
                'rubric_id' => $setting['rubric_id'] ?? null,
            ];
        })->filter()->sortBy(function (array $line): float {
            $position = array_search($line['display_code'], array_column(config('payroll_bulletin', []), 0), true);
            return $position !== false ? (float) $position : ($line['category'] === 'earning' ? 11.5 : 100.0);
        })->values()->all();

        return [
            'lines' => $lines,
            'display_settings' => [
                'origin' => is_array($stored) ? 'calculation_snapshot' : 'current_catalogue',
                'matched_rubric_count' => $matched,
                'hidden_line_count' => $hidden,
            ],
        ];
    }

    private function reference(string $code): ?array
    {
        $map = config('payroll_bulletin', []);
        return $map[$code] ?? collect($map)->first(fn ($item) => $item[0] === $code);
    }
}
