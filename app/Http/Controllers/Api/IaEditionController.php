<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ia\UpdateIaTeacherRequest;
use App\Services\PayrollEditionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * IA : modification des fiches (Gestion du personnel > Enseignants) et
 * validation de l'Édition du mois (Paie > États de présence > Édition).
 */
class IaEditionController extends Controller
{
    public function __construct(private readonly PayrollEditionService $edition) {}

    public function teacherForm(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->edition->teacherForm($request->user(), $id)]);
    }

    public function updateTeacher(UpdateIaTeacherRequest $request, int $id): JsonResponse
    {
        $result = $this->edition->updateTeacher($request->user(), $id, $request->editionData());

        return response()->json([
            'message' => $result['changes'] === []
                ? 'Aucune modification à enregistrer.'
                : 'Fiche mise à jour. Elle sera prise en compte à la validation de la prochaine Édition non encore validée (Paie > États de présence > Édition).',
            'data' => $result,
        ]);
    }

    /** Première génération de l'État des salaires du mois par l'IA (ensuite : « Consulter »). */
    public function statementGenerated(Request $request): JsonResponse
    {
        $data = $request->validate(['payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id']]);

        return response()->json(['data' => $this->edition->markStatementGenerated($request->user(), (int) $data['payroll_period_id'])]);
    }

    public function validateEdition(Request $request): JsonResponse
    {
        $data = $request->validate(['payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id']]);
        $key = trim((string) $request->header('Idempotency-Key')) ?: null;
        $result = $this->edition->validate($request->user(), (int) $data['payroll_period_id'], $request, $key);

        return response()->json([
            'message' => 'Édition validée : '.$result['payslip_count'].' bulletin(s) recalculé(s). L’État des salaires de votre IA est disponible.',
            'data' => $result,
        ]);
    }
}
