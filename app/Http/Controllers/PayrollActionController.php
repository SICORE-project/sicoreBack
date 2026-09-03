<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollActionRequest;
use App\Models\PayrollAuditLog;
use App\Services\PayrollActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PayrollActionController extends Controller
{
    public function __construct(private readonly PayrollActionService $actions) {}

    public function handle(PayrollActionRequest $request, string $action): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));
        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > 100 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $idempotencyKey)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Un identifiant d’opération valide est requis.',
            ]);
        }

        $existing = PayrollAuditLog::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Cette opération a déjà été traitée.',
                'data' => $existing->after,
                'replayed' => true,
            ]);
        }

        $data = $request->validated();
        $actor = $request->user();
        $resource = match ($action) {
            'configure-teacher-payroll' => $this->actions->configureTeacherPayroll(
                $data,
                $actor,
                $request,
                $idempotencyKey
            ),
            'create-period' => $this->actions->createPeriod($data, $actor, $request, $idempotencyKey),
            'save-attendance' => $this->actions->saveAttendance($data, $actor, $request, $idempotencyKey),
            'add-element' => $this->actions->addElement($data, $actor, $request, $idempotencyKey),
            'apply-tabaski-advance' => $this->actions->applyCollectiveTabaski(
                $data,
                'TABASKI_AVANCE',
                'Avance Tabaski',
                'earning',
                $actor,
                $request,
                $idempotencyKey
            ),
            'apply-tabaski-deduction' => $this->actions->applyCollectiveTabaski(
                $data,
                'TABASKI_RETENUE',
                'Retenue Tabaski',
                'deduction',
                $actor,
                $request,
                $idempotencyKey
            ),
            'exempt-element' => $this->actions->exemptElement($data, $actor, $request, $idempotencyKey),
            'validate-attendance' => $this->actions->validateInputs(
                (int) $data['payroll_period_id'],
                'attendance',
                $actor,
                $request,
                $idempotencyKey
            ),
            'validate-elements' => $this->actions->validateInputs(
                (int) $data['payroll_period_id'],
                'elements',
                $actor,
                $request,
                $idempotencyKey
            ),
            'calculate-payroll' => $this->actions->calculate(
                (int) $data['payroll_period_id'],
                $actor,
                $request,
                $idempotencyKey
            ),
            'validate-payroll' => $this->actions->validatePayroll(
                (int) $data['payroll_period_id'],
                $actor,
                $request,
                $idempotencyKey
            ),
            'close-period' => $this->actions->closePeriod($data, $actor, $request, $idempotencyKey),
            'mark-paid' => $this->actions->markPaid($data, $actor, $request, $idempotencyKey),
            default => abort(404, 'Action de paie inconnue.'),
        };

        return response()->json([
            'message' => $this->message($action),
            'data' => $resource,
            'operation_id' => $idempotencyKey,
        ], $action === 'create-period' ? 201 : 200);
    }

    private function message(string $action): string
    {
        return match ($action) {
            'configure-teacher-payroll' => 'Le profil de paie du formateur a été configuré.',
            'create-period' => 'La période de paie a été créée.',
            'save-attendance' => 'L’état de présence a été enregistré.',
            'add-element' => 'L’élément variable a été enregistré.',
            'apply-tabaski-advance' => 'L’avance Tabaski a été appliquée au groupe sélectionné.',
            'apply-tabaski-deduction' => 'La retenue Tabaski a été appliquée au groupe sélectionné.',
            'exempt-element' => 'L’exemption a été enregistrée.',
            'validate-attendance' => 'Les états de présence ont été validés.',
            'validate-elements' => 'Les éléments variables ont été validés.',
            'calculate-payroll' => 'Le calcul de paie est terminé et contrôlé.',
            'validate-payroll' => 'La paie a été validée.',
            'close-period' => 'La période a été clôturée définitivement.',
            'mark-paid' => 'Le paiement a été enregistré.',
            default => 'Opération effectuée.',
        };
    }
}
