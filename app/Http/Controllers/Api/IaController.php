<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\Ia;
use App\Services\Administration\IaPayrollReports;
use App\Services\Administration\IaScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IaController extends Controller
{
    public function __construct(private IaScope $scope) {}

    private function period(Request $request): ?object
    {
        $data = $request->validate(['period_id' => ['nullable', 'integer', 'exists:payroll_periods,id']]);

        return isset($data['period_id']) ? DB::table('payroll_periods')->find($data['period_id'])
            : DB::table('payroll_periods')->orderByDesc('start_date')->first();
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $id = $this->scope->id($user);
        $data = ['agent' => $user->nom_complet, 'ia' => DB::table('ias')->where('id', $id)->first(['id', 'libelle']), 'indicateurs' => []];
        if ($user->hasPermission('enseignants.read')) {
            $teachers = $this->scope->teachers($user);
            $data['indicateurs'] = [
                'agents' => (clone $teachers)->count(),
                'fonctionnaires' => (clone $teachers)->where('type_engagement', 'fonctionnaire')->count(),
                'non_fonctionnaires' => (clone $teachers)->whereIn('type_engagement', ['vacataire', 'contractuel'])->count(),
            ];
            foreach (['ief_id' => 'iefs', 'lieu_service_id' => 'lieu_de_services'] as $field => $table) {
                $data[$table] = (clone $teachers)->leftJoin($table.' as ref', fn ($join) => $join->on('ref.id', '=', 'e.'.$field)->where('ref.ia_id', $id)->whereNull('ref.deleted_at'))
                    ->select('ref.libelle')->selectRaw('COUNT(*) as total')->groupBy('ref.id', 'ref.libelle')->get();
            }
        }
        if ($user->hasPermission('paie.bulletins.read')) {
            $period = $this->period($request);
            $data['periode'] = $period ? ['id' => $period->id, 'code' => $period->code] : null;
            if ($period) {
                $slips = $this->scope->payslips($user)->where('p.payroll_period_id', $period->id);
                $data['indicateurs']['bulletins_generes'] = (clone $slips)->count();
                if ($user->hasPermission('paie.sommes_percues.read')) {
                    $paid = (clone $slips)->where('p.payment_status', 'paid');
                    $data['indicateurs']['bulletins_payes'] = (clone $paid)->count();
                    $data['indicateurs']['sommes_percues'] = (clone $paid)->sum('p.net_amount');
                }
                $data['indicateurs']['bulletins_restants'] = $this->scope->teachers($user)->where('e.est_actif', true)
                    ->where('e.statut', 'en_activite')->whereNotIn('e.id', (clone $slips)->select('p.enseignant_id'))->count();
                if ($user->hasPermission('paie.masse_salariale.read')) {
                    $data['indicateurs']['masse_salariale'] = (clone $slips)->sum('p.gross_amount');
                }
                $data['dernieres_operations'] = (clone $slips)->orderByDesc('p.updated_at')->limit(10)
                    ->get(['p.id', 'e.nom', 'e.prenom', 'p.payment_status', 'p.updated_at']);
            }
        }

        return response()->json(['data' => $data]);
    }

    public function teachers(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'], 'ief_id' => ['nullable', 'integer'], 'lieu_service_id' => ['nullable', 'integer'], 'engagement' => ['nullable', 'in:non_fonctionnaires']]);
        $query = $this->scope->teachers($request->user());
        if (($data['engagement'] ?? null) === 'non_fonctionnaires') {
            $query->whereIn('e.type_engagement', ['contractuel', 'vacataire']);
        }
        foreach (['ief_id', 'lieu_service_id'] as $field) {
            if (! empty($data[$field])) {
                $query->where('e.'.$field, $data[$field]);
            }
        }
        foreach (preg_split('/\s+/u', trim($data['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(fn ($q) => $q->where('e.nom', 'like', '%'.$term.'%')->orWhere('e.prenom', 'like', '%'.$term.'%')->orWhere('e.matricule', 'like', '%'.$term.'%'));
        }

        return response()->json($query->orderBy('e.nom')->orderBy('e.id')->paginate(20, ['e.id', 'e.matricule', 'e.nom', 'e.prenom', 'e.statut', 'e.date_prise_service', 'e.type_engagement']));
    }

    public function teacher(Request $request, int $id)
    {
        $teacher = $this->scope->teachers($request->user())->where('e.id', $id)->first(['e.id', 'e.matricule', 'e.nom', 'e.prenom', 'e.statut', 'e.type_engagement', 'e.date_prise_service', 'e.ia_id', 'e.ief_id', 'e.lieu_service_id']);
        abort_unless($teacher, 403);

        return response()->json(['data' => $teacher]);
    }

    public function references(Request $request)
    {
        $id = $this->scope->id($request->user());

        return response()->json(['data' => [
            'ia' => Ia::with('region:id,nom')->findOrFail($id)->only(['id', 'code', 'libelle', 'region_id', 'region']),
            'iefs' => DB::table('iefs')->where('ia_id', $id)->whereNull('deleted_at')->get(['id', 'code', 'libelle', 'ia_id']),
            'etablissements' => DB::table('lieu_de_services')->where('ia_id', $id)->whereNull('deleted_at')->where('est_actif', true)->get(['id', 'libelle', 'ief_id']),
        ]]);
    }

    public function payroll(Request $request)
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'view' => ['nullable', 'string', 'in:paid,salaries,contributions,workforce,banks']]);
        $user = $request->user();
        $period = $this->period($request);
        $scopedPayslips = $this->scope->payslips($user);
        $query = (clone $scopedPayslips)->where('p.payroll_period_id', $period?->id ?? 0);
        $columns = ['p.id', 'e.matricule', 'e.nom', 'e.prenom', 'p.gross_amount', 'p.net_amount', 'p.payment_status'];
        if ($request->routeIs('ia.payroll.export')) {
            abort_unless($user->hasPermission('paie.bulletins.export'), 403);

            return response()->streamDownload(function () use ($query, $columns) {
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['ID', 'Matricule', 'Nom', 'Prénom', 'Brut', 'Net', 'Paiement'], ';', '"', '');
                foreach ($query->orderBy('p.id')->select($columns)->cursor() as $row) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\-\t\r]/', $v) ? "'".$v : $v, (array) $row), ';', '"', '');
                }
                fclose($out);
            }, 'bulletins-ia.csv', ['Cache-Control' => 'no-store, private']);
        }
        $paid = (clone $query)->where('p.payment_status', 'paid');
        $teachers = $this->scope->teachers($user);
        $payrollModules = [
            'paie-bulletins' => [
                'permission' => 'paie.bulletins.read',
                'label' => 'Bulletins de paie',
                'endpoint' => '/api/ia/paie',
            ],
            'paie-sommes-percues' => [
                'permission' => 'paie.sommes_percues.read',
                'label' => 'Sommes perçues',
                'endpoint' => '/api/ia/paie?view=paid',
            ],
            'paie-etat-salaires' => [
                'permission' => 'paie.etat_salaires.read',
                'label' => 'État des salaires',
                'endpoint' => '/api/ia/paie?view=salaries',
            ],
            'paie-cotisations-sociales' => [
                'permission' => 'paie.cotisations.read',
                'label' => 'Cotisations sociales',
                'endpoint' => '/api/ia/paie?view=contributions',
            ],
            'paie-effectifs-ief' => [
                'permission' => 'paie.effectifs_ief.read',
                'label' => 'Effectifs par IEF',
                'endpoint' => '/api/ia/paie?view=workforce',
            ],
            'paie-recapitulatif-banque' => [
                'permission' => 'paie.recap_banque.read',
                'label' => 'Récapitulatif par banque',
                'endpoint' => '/api/ia/paie?view=banks',
            ],
        ];
        $viewPermissions = [
            'paid' => 'paie.sommes_percues.read',
            'salaries' => 'paie.etat_salaires.read',
            'contributions' => 'paie.cotisations.read',
            'workforce' => 'paie.effectifs_ief.read',
            'banks' => 'paie.recap_banque.read',
        ];
        $view = (string) $request->query('view', '');
        if ($view && isset($viewPermissions[$view])) {
            abort_unless($user->hasPermission($viewPermissions[$view]), 403);
        }
        $indicators = [
            'agents' => (clone $teachers)->count(),
        ];
        if ($user->hasPermission('paie.bulletins.read')) {
            $indicators['bulletins_generes'] = (clone $query)->count();
        }
        if ($user->hasPermission('paie.sommes_percues.read')) {
            $indicators['bulletins_payes'] = (clone $paid)->count();
            $indicators['sommes_percues'] = (clone $paid)->sum('p.net_amount');
        }
        if ($user->hasPermission('paie.masse_salariale.read')) {
            $indicators['masse_salariale'] = (clone $query)->sum('p.gross_amount');
        }
        $report = app(IaPayrollReports::class)->report($user, $period?->id, $view);
        if ($view === 'paid') {
            $query->where('p.payment_status', 'paid');
        }

        return response()->json([
            'modules' => collect($payrollModules)
                ->filter(fn (array $module): bool => $user->hasPermission($module['permission']))
                ->map(fn (array $module, string $slug): array => [
                    'slug' => $slug,
                    'label' => $module['label'],
                    'endpoint' => $module['endpoint'],
                ])
                ->values(),
            'periode' => $period ? ['id' => $period->id, 'code' => $period->code] : null,
            'periodes' => DB::table('payroll_periods')->orderByDesc('start_date')->get(['id', 'code']),
            'indicateurs' => $indicators,
            'rapport' => $report,
            'bulletins' => $query->orderBy('p.id')->paginate(20, $columns),
        ]);
    }

    public function payslip(Request $request, int $id)
    {
        $slip = $this->scope->payslips($request->user())->where('p.id', $id)->first(['p.id', 'e.nom', 'e.prenom', 'e.matricule', 'p.gross_amount', 'p.deduction_amount', 'p.net_amount', 'p.payment_status']);
        abort_unless($slip, 403);

        return response()->json(['data' => $slip]);
    }
}
