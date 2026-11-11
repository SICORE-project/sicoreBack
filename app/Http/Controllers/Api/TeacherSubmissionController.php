<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Models\Personnel\TeacherSubmission;
use App\Services\Administration\IaScope;
use App\Services\Administration\Personnel\TeacherSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherSubmissionController extends Controller
{
    public function options(Request $request, IaScope $scope)
    {
        $ia = $scope->id($request->user());
        $tables = ['statutOptions' => 'statuts_enseignant', 'corpsOptions' => 'corps_enseignant',
            'categorieOptions' => 'categories', 'diplomeOptions' => 'diplomes', 'disciplineOptions' => 'disciplines',
            'institutionOptions' => 'instituts_financieres', 'syndicatOptions' => 'syndicats'];
        $data = [];
        foreach ($tables as $key => $table) {
            $query = DB::table($table);
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at')) $query->whereNull('deleted_at');
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'est_actif')) $query->where('est_actif', true);
            $data[$key] = $query->get();
        }
        $data['academies'] = DB::table('ias')->where('id', $ia)->get();
        $data['iefOptions'] = DB::table('iefs')->where('ia_id', $ia)->whereNull('deleted_at')->get();
        $data['etablissements'] = DB::table('lieu_de_services')->where('ia_id', $ia)->whereNull('deleted_at')->get();
        $data['regionOptions'] = [];
        return response()->json(['data' => $data]);
    }

    public function store(StoreEnseignantRequest $request, TeacherSubmissionService $service)
    {
        $submission = $service->submit($request->user(), $request->validated());
        return response()->json(['data' => ['id' => $submission->id],
            'message' => 'Dossier soumis à la DAGE. Il apparaîtra dans la liste des enseignants après validation.'], 201);
    }

    public function index(Request $request, IaScope $scope)
    {
        $query = TeacherSubmission::query();
        if ($request->user()->hasRole('gestionnaire_ia')) $query->where('teacher_submissions.ia_id', $scope->id($request->user()));
        else {
            abort_unless($request->user()->hasRole('super_admin'), 403);
            $query->where('status', 'pending');
        }
        $page = $query->orderByDesc('id')->paginate(20);
        $page->through(function ($submission) {
            $payload = $submission->payload;
            foreach (['ia' => ['ias', 'ia_id'], 'ief' => ['iefs', 'ief_id'],
                'corps' => ['corps_enseignant', 'corps_id'], 'statut_enseignant' => ['statuts_enseignant', 'statut_enseignant_id'],
                'categorie' => ['categories', 'categorie_id'], 'diplome' => ['diplomes', 'diplome_id'],
                'discipline' => ['disciplines', 'discipline_id'], 'lieu_service' => ['lieu_de_services', 'lieu_service_id']]
                as $key => [$table, $field]) {
                $payload[$key] = empty($payload[$field]) ? null : DB::table($table)->find($payload[$field]);
            }
            $bankId = data_get($payload, 'compte_bancaire.institut_financier_id');
            $payload['compte_bancaire']['institut_financier'] = $bankId ? DB::table('instituts_financieres')->find($bankId) : null;
            return array_merge($submission->toArray(), ['payload' => $payload]);
        });
        return response()->json(['data' => $page]);
    }

    public function approve(Request $request, int $id, TeacherSubmissionService $service)
    {
        $service->approve($request->user(), $id);
        return response()->json(['message' => 'Enseignant validé et ajouté à la liste des enseignants.']);
    }
}
