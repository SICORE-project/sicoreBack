<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use App\Services\RecruitmentAccess;
use App\Services\RecruitmentImporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruitmentController extends Controller
{
    public function __construct(private RecruitmentAccess $access) {}

    public function template()
    {
        return response(implode(';', RecruitmentImporter::HEADERS)."\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="modele-recrutement.csv"',
        ]);
    }

    public function index(Request $request)
    {
        $ids = $this->access->members($request->user())->select('m.batch_id');

        return response()->json(['data' => DB::table('recruitment_batches')->whereIn('id', $ids)->orderByDesc('id')
            ->get(['id', 'reference', 'recruited_at', 'transmitted_at', 'created_at'])]);
    }

    public function show(Request $request, int $id)
    {
        $batch = $this->access->batch($request->user(), $id);
        $members = $this->access->members($request->user())->where('m.batch_id', $id)
            ->select('m.id', 'm.enseignant_id', 'm.engagement', 'm.service_date', 'm.engagement_since', 'm.alerted_at',
                'e.matricule', 'e.prenom', 'e.nom', 'e.est_actif', 'e.ia_id', 'e.ief_id', 'e.lieu_service_id')->orderBy('m.id')->get();
        $events = DB::table('recruitment_events')->where('batch_id', $id)
            ->where(fn ($q) => $q->whereNull('member_id')->orWhereIn('member_id', $members->pluck('id')))
            ->orderByDesc('id')->get()->map(function ($event) {
                $event->has_document = (bool) $event->document_path;
                unset($event->document_path);

                return $event;
            });
        $batch->has_os = (bool) $batch->os_path;
        unset($batch->os_path);
        $this->event($id, $request->user()->id, 'consultation');

        return response()->json(['data' => ['batch' => $batch, 'members' => $members, 'history' => $events]]);
    }

    public function import(Request $request, RecruitmentImporter $importer)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', 'unique:recruitment_batches,reference'],
            'recruited_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'], 'preview' => ['nullable', 'boolean'],
        ]);
        $rows = $importer->rows($request->file('file'), $request->user());
        if ($request->boolean('preview')) {
            return response()->json(['data' => ['total' => count($rows), 'rows' => $rows]]);
        }
        $id = DB::transaction(function () use ($data, $rows, $request) {
            $id = DB::table('recruitment_batches')->insertGetId([
                'reference' => $data['reference'], 'recruited_at' => $data['recruited_at'], 'created_by' => $request->user()->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($rows as $row) {
                $engagement = $row['type_engagement'];
                // Identifiant technique provisoire, jamais présenté comme matricule officiel.
                if (! $row['matricule']) {
                    $row['matricule'] = 'TMP'.strtoupper(Str::random(6));
                }
                $teacher = DB::table('enseignants')->insertGetId($row + [
                    'date_recrutement' => $data['recruited_at'], 'est_actif' => false, 'statut' => 'en_activite',
                    'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $member = DB::table('recruitment_members')->insertGetId([
                    'batch_id' => $id, 'enseignant_id' => $teacher, 'engagement' => $engagement,
                    'engagement_since' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->event($id, $request->user()->id, 'recrutement', $member, null, $engagement, $data['recruited_at']);
            }

            return $id;
        });

        return response()->json(['data' => ['id' => $id, 'total' => count($rows)]], 201);
    }

    public function os(Request $request, int $id)
    {
        $request->validate(['document' => ['required', 'file', 'mimes:pdf', 'max:10240']]);
        $this->access->manageBatch($request->user(), $id);
        $path = $request->file('document')->store('recruitment/os', 'local');
        try {
            DB::transaction(function () use ($request, $id, $path) {
                $batch = DB::table('recruitment_batches')->where('id', $id)->lockForUpdate()->first();
                abort_if($batch->transmitted_at, 409, 'Un lot transmis ne peut plus être modifié.');
                DB::table('recruitment_batches')->where('id', $id)->update(['os_path' => $path, 'updated_at' => now()]);
                $this->event($id, $request->user()->id, 'ordre_service', null, null, null, null, $path);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json(['message' => 'Ordre de service enregistré.']);
    }

    public function transmit(Request $request, int $id)
    {
        $this->access->manageBatch($request->user(), $id);
        DB::transaction(function () use ($request, $id) {
            $batch = DB::table('recruitment_batches')->where('id', $id)->lockForUpdate()->first();
            abort_if($batch->transmitted_at, 409, 'Ce lot est déjà transmis.');
            abort_unless($batch->os_path, 422, 'Joignez l’ordre de service avant la transmission.');
            $recipients = User::where('statut', 'actif')->whereHas('lieuService', fn ($q) => $q->where('type', 'DAGE')->where('est_actif', true))
                ->whereHas('role.permissions', fn ($q) => $q->where('slug', 'recruitment.read'))->get();
            abort_if($recipients->isEmpty(), 422, 'Aucun destinataire DAGE actif avec la permission de consultation des recrutements.');
            DB::table('recruitment_batches')->where('id', $id)->update(['transmitted_at' => now(), 'updated_at' => now()]);
            foreach ($recipients as $recipient) {
                DB::table('recruitment_notices')->insert([
                    'user_id' => $recipient->id, 'batch_id' => $id, 'message' => 'Nouveau lot reçu : '.$batch->reference, 'created_at' => now(),
                ]);
            }
            $this->event($id, $request->user()->id, 'transmission_dage');
        });

        return response()->json(['message' => 'Lot transmis à la DAGE.']);
    }

    public function service(Request $request, int $member)
    {
        $data = $request->validate([
            'service_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'lieu_service_id' => ['required', 'integer', 'exists:lieu_de_services,id'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);
        $user = $request->user();
        $structure = $user->lieuService;
        abort_unless($structure && $structure->est_actif && strtoupper($structure->type) === 'IA' && $structure->ia_id, 403);
        $visible = $this->access->members($user)->where('m.id', $member)->whereNotNull('b.transmitted_at')->first(['m.id']);
        abort_unless($visible, 404);
        $lieu = LieuService::where('est_actif', true)->where('ia_id', $structure->ia_id)->findOrFail($data['lieu_service_id']);
        $path = $request->file('document')->store('recruitment/certificates', 'local');
        try {
            DB::transaction(function () use ($data, $user, $member, $lieu, $path) {
                $record = DB::table('recruitment_members')->where('id', $member)->lockForUpdate()->first();
                abort_if($record->service_date, 409, 'Prise de service déjà enregistrée.');
                $batch = DB::table('recruitment_batches')->find($record->batch_id);
                abort_if($data['service_date'] < $batch->recruited_at, 422, 'La prise de service ne peut pas précéder le recrutement.');
                DB::table('recruitment_members')->where('id', $member)->update([
                    'service_date' => $data['service_date'], 'certificate_path' => $path, 'service_recorded_by' => $user->id,
                    'engagement_since' => $data['service_date'], 'updated_at' => now(),
                ]);
                DB::table('enseignants')->where('id', $record->enseignant_id)->update([
                    'est_actif' => true, 'date_prise_service' => $data['service_date'], 'statut' => 'en_activite',
                    'lieu_service_id' => $lieu->id, 'ia_id' => $lieu->ia_id, 'ief_id' => $lieu->ief_id,
                    'updated_by' => $user->id, 'updated_at' => now(),
                ]);
                $this->event($record->batch_id, $user->id, 'prise_service', $member, 'inactif', 'actif', $data['service_date'], $path);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json(['message' => 'Prise de service enregistrée. Le recruté est actif.']);
    }

    public function transition(Request $request, int $member)
    {
        $data = $request->validate(['effective_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'engagement' => ['required', 'in:contractuel'], 'document' => ['required', 'file', 'mimes:pdf', 'max:10240']]);
        abort_unless($this->access->members($request->user())->where('m.id', $member)->exists(), 404);
        $path = $request->file('document')->store('recruitment/decisions', 'local');
        try {
            DB::transaction(function () use ($request, $member, $data, $path) {
                $record = DB::table('recruitment_members')->where('id', $member)->lockForUpdate()->first();
                $next = ['vacataire' => 'contractuel'][$record->engagement] ?? null;
                abort_unless($next === $data['engagement'], 422, 'Transition de statut invalide.');
                abort_unless($record->service_date && Carbon::parse($record->service_date)->addYearsNoOverflow(2)->toDateString() <= $data['effective_date'], 422, 'Deux ans de prise de service sont requis.');
                DB::table('recruitment_members')->where('id', $member)->update([
                    'engagement' => $data['engagement'], 'engagement_since' => $data['effective_date'], 'alerted_at' => null, 'updated_at' => now(),
                ]);
                DB::table('enseignants')->where('id', $record->enseignant_id)->update([
                    'type_engagement' => $data['engagement'], 'updated_by' => $request->user()->id, 'updated_at' => now(),
                ]);
                $this->event($record->batch_id, $request->user()->id, 'changement_statut', $member, $record->engagement, $data['engagement'], $data['effective_date'], $path);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json(['message' => 'Changement de statut validé et historisé.']);
    }

    public function document(Request $request, int $event)
    {
        $record = DB::table('recruitment_events')->find($event);
        abort_unless($record && $record->document_path, 404);
        $this->access->batch($request->user(), $record->batch_id);
        if ($record->member_id) {
            abort_unless($this->access->members($request->user())->where('m.id', $record->member_id)->exists(), 404);
        }
        $this->event($record->batch_id, $request->user()->id, 'consultation_document', $record->member_id);

        return Storage::disk('local')->download($record->document_path, 'justificatif.pdf');
    }

    public function notices(Request $request)
    {
        return response()->json(['data' => DB::table('recruitment_notices')->where('user_id', $request->user()->id)->orderByDesc('id')->limit(100)->get()]);
    }

    public function establishments(Request $request)
    {
        $structure = $request->user()->lieuService;
        abort_unless($structure && $structure->est_actif && strtoupper($structure->type) === 'IA' && $structure->ia_id, 403);
        $query = LieuService::where('est_actif', true)->where('ia_id', $structure->ia_id)->whereNotNull('ief_id');
        if ($request->user()->ief_id) {
            $query->where('ief_id', $request->user()->ief_id);
        }

        return response()->json(['data' => $query->orderBy('libelle')->get(['id', 'libelle', 'ia_id', 'ief_id'])]);
    }

    public function readNotice(Request $request, int $id)
    {
        abort_unless(DB::table('recruitment_notices')->where('id', $id)->where('user_id', $request->user()->id)->exists(), 404);
        DB::table('recruitment_notices')->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification lue.']);
    }

    private function event(int $batch,int $user,string $action,?int $member = null,?string $previous = null,?string $next = null,?string $date = null,?string $document = null): void
    {
        DB::table('recruitment_events')->insert(['batch_id' => $batch, 'user_id' => $user, 'action' => $action, 'member_id' => $member,
            'previous_status' => $previous, 'new_status' => $next, 'effective_date' => $date, 'document_path' => $document, 'created_at' => now()]);
    }
}
