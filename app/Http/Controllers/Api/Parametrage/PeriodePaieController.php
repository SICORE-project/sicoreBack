<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StorePeriodePaieRequest;
use App\Http\Requests\Parametrage\UpdatePeriodePaieRequest;
use App\Models\Paie\PeriodePaie;
use App\Support\Rattachements;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodePaieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $periodes = PeriodePaie::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(libelle) LIKE ?', [$term]);
                });
            })
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString();

        $cycles = \App\Models\PayrollPeriod::orderByDesc('start_date')->get(['id', 'code', 'label', 'status', 'start_date', 'end_date', 'closed_at']);
        $periodes->getCollection()->each(function ($reference) use ($cycles) {
            $month = app(\App\Services\PayrollPeriodCatalogue::class)->month($reference);
            $cycle = $reference->payroll_period_id ? $cycles->firstWhere('id', $reference->payroll_period_id)
                : $cycles->first(fn ($cycle) => $cycle->code === $reference->code || (! preg_match('/^\d{4}-/', $reference->code) && (int) substr($cycle->code, 5, 2) === $month));
            $reference->setAttribute('cycle_status', $cycle?->status ?? 'not_opened');
            $reference->setAttribute('cycle_closed_at', $cycle?->closed_at?->toIso8601String());
            $reference->setAttribute('display_start_date', $reference->date_debut ?: $cycle?->start_date?->toDateString());
            $reference->setAttribute('display_end_date', $reference->date_fin ?: $cycle?->end_date?->toDateString());
            $reference->setAttribute('auto_close_enabled', $reference->payroll_period_id !== null && $reference->date_fin !== null);
        });
        return response()->json(['data' => $periodes, 'payroll_periods' => $cycles]);
    }

    public function store(StorePeriodePaieRequest $request): JsonResponse
    {
        app(\App\Services\PayrollPeriodAutoClosure::class)->run();
        $periode = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $reference = PeriodePaie::query()->create($request->validated());
            app(\App\Services\PayrollPeriodLifecycle::class)->open($reference, $request->user(), $request);
            return $reference;
        });

        return response()->json([
            'message' => 'Période créée et paie calculée automatiquement pour toutes les IA.',
            'data' => $periode,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => PeriodePaie::query()->findOrFail($id),
        ]);
    }

    public function open(Request $request, int $id): JsonResponse
    {
        app(\App\Services\PayrollPeriodAutoClosure::class)->run();
        $period = app(\App\Services\PayrollPeriodLifecycle::class)->open(PeriodePaie::findOrFail($id), $request->user(), $request);
        return response()->json(['message' => 'Période ouverte : toutes les IA ont été calculées.', 'data' => $period]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $period = app(\App\Services\PayrollPeriodLifecycle::class)->close($id, $request->user(), $request);
        return response()->json(['message' => 'Période clôturée. Les bulletins restent consultables.', 'data' => $period]);
    }

    public function update(UpdatePeriodePaieRequest $request, int $id): JsonResponse
    {
        $periode = PeriodePaie::query()->findOrFail($id);
        if ($periode->code !== ($request->validated()['code'] ?? $periode->code)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'Le code d’une période créée ne peut plus être modifié. Créez une nouvelle période après clôture.']);
        }
        $periode->update($request->validated());

        return response()->json([
            'message' => 'Période de paie modifiée avec succès.',
            'data' => $periode->refresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $periode = PeriodePaie::query()->findOrFail($id);
        if ($periode->payroll_period_id) {
            return response()->json(['message' => 'Cette période possède un historique de paie et une clôture programmée ; elle ne peut pas être supprimée.'], 409);
        }

        $message = Rattachements::message("la période « {$periode->libelle} »", [
            [Rattachements::compter('heures_supplementaires', 'periode_paie_id', $id), 'heure supplémentaire', 'heures supplémentaires'],
            [Rattachements::compter('mise_a_jour_mc_ve', 'periode_paie_id', $id), 'mise à jour MC/VE', 'mises à jour MC/VE'],
            [Rattachements::compter('mise_a_jour_pc_vac', 'periode_paie_id', $id), 'mise à jour PC/VAC', 'mises à jour PC/VAC'],
        ]);

        if ($message !== null) {
            return response()->json(['message' => $message], 409);
        }

        try {
            $periode->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cette période de paie est déjà utilisée et ne peut pas être supprimée.',
            ], 409);
        }

        return response()->json([
            'message' => 'Période de paie supprimée avec succès.',
        ]);
    }
}
