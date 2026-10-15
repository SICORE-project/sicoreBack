<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollPayslip;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherPersonalController extends Controller
{
    private function teacher(Request $request)
    {
        abort_unless($request->user()?->hasRole('enseignant') && $request->user()->statut === 'actif', 403);
        $teacher = $request->user()->enseignant;
        abort_unless($teacher, 403, 'Votre compte doit être associé à votre dossier enseignant par un administrateur.');

        return $teacher;
    }

    private function available(Request $request)
    {
        return PayrollPayslip::query()->where('enseignant_id', $this->teacher($request)->id)
            ->whereHas('period', fn ($q) => $q->whereIn('status', ['validated', 'closed']));
    }

    public function profile(Request $request)
    {
        $teacher = $this->teacher($request)->load(['corps', 'categorie', 'specialite', 'ia', 'ief', 'lieuService', 'statutEnseignant']);
        $data = $teacher->only(['matricule', 'prenom', 'nom', 'lieu_naissance', 'genre', 'telephone', 'email', 'adresse', 'statut']);
        $data['date_naissance'] = $teacher->date_naissance?->format('d/m/Y');
        foreach (['corps', 'categorie', 'specialite', 'ia', 'ief', 'lieuService', 'statutEnseignant'] as $relation) {
            $data[$relation] = $teacher->$relation?->libelle ?? $teacher->$relation?->nom;
        }
        $data['statut_professionnel'] = match ($teacher->type_engagement) {
            'fonctionnaire' => 'Fonctionnaire',
            'contractuel', 'vacataire' => 'Non-fonctionnaire',
            default => null,
        };
        $data['indice'] = $teacher->type_engagement === 'fonctionnaire' ? $teacher->getAttribute('indice') : null;
        $data['statut'] = str_replace('_', ' ', (string) $teacher->statut);
        $data['bulletins_disponibles'] = $this->available($request)->count();

        return response()->json(['data' => $data])->header('Cache-Control', 'no-store, private');
    }

    public function payslips(Request $request)
    {
        $filters = $request->validate(['annee' => ['nullable', 'integer', 'between:1900,2200'], 'periode_id' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1']]);
        $base = $this->available($request);
        $periods = PayrollPeriod::whereIn('id', (clone $base)->select('payroll_period_id'))->orderByDesc('start_date')->get();
        $items = $base->with('period')
            ->when($filters['annee'] ?? null, fn ($q, $year) => $q->whereHas('period', fn ($q) => $q->whereYear('start_date', $year)))
            ->when($filters['periode_id'] ?? null, fn ($q, $period) => $q->where('payroll_period_id', $period))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(10);
        $items->through(fn ($p) => [
            'id' => $p->id, 'reference' => $p->reference, 'periode' => $p->period->label,
            'annee' => $p->period->start_date->year, 'date_generation' => $p->created_at?->format('d/m/Y'),
            'statut' => $p->period->status === 'closed' ? 'Clôturé' : 'Validé', 'net' => $p->net_amount,
        ]);

        return response()->json(['data' => $items, 'periodes' => $periods->map(fn ($p) => ['id' => $p->id, 'libelle' => $p->label]),
            'annees' => $periods->map(fn ($p) => $p->start_date->year)->unique()->values()])->header('Cache-Control', 'no-store, private');
    }

    public function pdf(Request $request, int $id)
    {
        $request->validate(['download' => ['nullable', 'boolean']]);
        $payslip = $this->available($request)->with(['period', 'lines'])->findOrFail($id);
        $teacher = $this->teacher($request);
        $pdf = Pdf::loadView('pdf.teacher-payslip', compact('payslip', 'teacher'));
        $filename = 'bulletin-'.$payslip->id.'.pdf';
        \App\Models\PayrollAuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $request->boolean('download') ? 'teacher.payslip.download' : 'teacher.payslip.view',
            'auditable_type' => PayrollPayslip::class,
            'auditable_id' => $payslip->id,
        ]);
        $response = $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
