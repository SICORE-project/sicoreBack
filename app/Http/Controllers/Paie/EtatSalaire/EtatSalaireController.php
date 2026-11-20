<?php

namespace App\Http\Controllers\Paie\EtatSalaire;

use App\Http\Controllers\PayrollPageController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API de l'état mensuel des salaires (slug paie-etat-salaires).
 *
 * Les filtres sont validés ici ; les données sont construites par
 * App\Services\Paie\EtatSalaire\EtatSalaireReport. Le périmètre du
 * gestionnaire IA et le format CSV sont hérités de PayrollPageController.
 */
class EtatSalaireController extends PayrollPageController
{
    private const SLUG = 'paie-etat-salaires';

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->page($request, self::SLUG, $this->filters($request)),
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        abort_if($request->user()?->hasRole('gestionnaire_ia') && ! $request->user()->hasPermission('paie.bulletins.export'), 403);

        return $this->csvDownload($this->page($request, self::SLUG, $filters), self::SLUG);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'academic_year_id' => ['nullable', 'integer'],
            'statut_enseignant_id' => ['nullable', 'integer'],
            'ia_id' => ['nullable', 'integer'],
            'ief_id' => ['nullable', 'integer'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'payment_place_id' => ['nullable', 'integer'],
            'training_center_id' => ['nullable', 'integer'],
            'tabaski_only' => ['nullable', 'boolean'],
            'with_signature' => ['nullable', 'boolean'],
            'without_service_done' => ['nullable', 'boolean'],
            'dage_signatory' => ['nullable', 'boolean'],
            // Bulletins décochés dans l'écran de consultation IA (exclus de l'export).
            'exclude' => ['nullable', 'array', 'max:5000'],
            'exclude.*' => ['string', 'max:100'],
        ]);
    }
}
