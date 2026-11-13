<?php
namespace App\Http\Controllers\Api\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Services\Administration\Personnel\EnseignantService;
use App\Services\Administration\Personnel\TeacherSubmissionService;
use App\Services\Administration\Personnel\TeacherImportReferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherImportController extends Controller
{
    public function store(Request $request, EnseignantService $service, TeacherSubmissionService $submissions, TeacherImportReferences $references)
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $contents = file_get_contents($request->file('csv')->getRealPath());
        if (str_starts_with($contents, "\xFF\xFE")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($contents, "\xFE\xFF")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        } elseif (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }
        if (str_contains($contents, "\0") || ! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages(['csv' => 'Le fichier contient un encodage illisible. Enregistrez-le au format CSV UTF-8 depuis Excel, puis réessayez.']);
        }
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);
        try {
            $first = fgets($handle);
            if ($first === false) throw ValidationException::withMessages(['csv' => 'Le fichier est vide.']);
            $delimiter = ';';
            $columnCount = 0;
            foreach ([';', ',', "\t"] as $candidate) {
                $count = count(str_getcsv($first, $candidate, '"', ''));
                if ($count > $columnCount) {
                    $delimiter = $candidate;
                    $columnCount = $count;
                }
            }
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            $headers = array_map(fn ($value) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)), $headers);
            $required = ['matricule', 'nom', 'prenom', 'date_naissance', 'cni', 'telephone',
                'statut_enseignant_id', 'ief_id', 'code_banque', 'code_guichet', 'numero_compte'];
            $canonicalHeaders = array_map(fn ($header) => TeacherImportReferences::FIELDS[$header][0] ?? $header, $headers);
            $missing = array_diff($required, $canonicalHeaders);
            if ($missing || count($headers) !== count(array_unique($headers))) {
                $names = array_flip(array_map(fn ($reference) => $reference[0], TeacherImportReferences::FIELDS));
                $missing = array_map(fn ($field) => $names[$field] ?? $field, array_values($missing));
                throw ValidationException::withMessages(['csv' => $missing
                    ? 'Colonnes obligatoires manquantes : '.implode(', ', $missing).'. Séparateurs acceptés : point-virgule, virgule ou tabulation.'
                    : 'L’en-tête contient des colonnes en double. Chaque colonne doit apparaître une seule fois.']);
            }
            // Reject nested/unrecognised columns before building the banking payload.
            $allowed = array_merge(array_keys((new StoreEnseignantRequest)->rules()),
                ['code_banque', 'code_guichet', 'numero_compte', 'cle_rib'], array_keys(TeacherImportReferences::FIELDS));
            $allowed = array_filter($allowed, fn ($field) => ! str_contains($field, '.')
                && ! in_array($field, ['compte_bancaire', 'syndicat', 'mutuelle'], true));
            if (array_diff($headers, $allowed)) {
                throw ValidationException::withMessages(['csv' => 'Colonne(s) inconnue(s) : '.implode(', ', array_diff($headers, $allowed)).'.']);
            }
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($values) === 1 && trim((string) $values[0]) === '') continue;
                if (count($rows) >= 500) throw ValidationException::withMessages(['csv' => 'Le maximum est de 500 enseignants par fichier.']);
                if (count($values) !== count($headers)) {
                    $rows[$line] = ['_csv_error' => ['csv' => ['Nombre de colonnes incorrect : '.count($values).' au lieu de '.count($headers).'.']]];
                    continue;
                }
                $data = array_combine($headers, array_map(fn ($value) => trim((string) $value), $values));
                foreach ($data as $key => $value) if ($value === '') $data[$key] = null;
                foreach (['code_banque', 'code_guichet', 'numero_compte', 'cle_rib'] as $field) {
                    $data['compte_bancaire'][$field] = $data[$field] ?? null;
                    unset($data[$field]);
                }
                $data['est_actif'] = true;
                $rows[$line] = $data;
            }
            if (!$rows) throw ValidationException::withMessages(['csv' => 'Le fichier ne contient aucun enseignant.']);
            $pending = $request->user()->hasRole('gestionnaire_ia');
            $accepted = [];
            $blocked = [];
            foreach ($rows as $line => $data) {
                $identity = ['line' => $line, 'matricule' => $data['matricule'] ?? null,
                    'nom' => $data['nom'] ?? null, 'prenom' => $data['prenom'] ?? null];
                if (isset($data['_csv_error'])) {
                    $blocked[] = $identity + ['errors' => $data['_csv_error']];
                    continue;
                }
                try {
                    $data = $references->resolve($data, $request->user());
                    // Each row commits independently; a rejected row leaves no partial records.
                    $record = DB::transaction(function () use ($data, $request, $service, $submissions, $pending) {
                        $validation = StoreEnseignantRequest::create('/api/admin/personnel/enseignants', 'POST', $data);
                        $validation->setContainer(app())->setRedirector(app('redirect'));
                        $validation->setUserResolver(fn () => $request->user());
                        $validation->validateResolved();
                        return $pending
                            ? $submissions->submit($request->user(), $validation->validated())
                            : $service->create($validation->validated(), $request->user()->id);
                    });
                    $accepted[] = $identity + ['id' => $record->id];
                } catch (ValidationException $exception) {
                    $blocked[] = $identity + ['errors' => $exception->errors()];
                }
            }
            $count = count($accepted);
            return response()->json([
                'message' => $count.($pending
                    ? ' dossier(s) soumis à la DAGE, en attente de validation.'
                    : ' enseignant(s) inséré(s).').' '.count($blocked).' ligne(s) bloquée(s).',
                'count' => $count, 'blocked_count' => count($blocked),
                'status' => $pending ? 'pending' : 'created',
                'accepted' => $accepted, 'blocked' => $blocked,
            ], $count ? 201 : 200);
        } finally {
            fclose($handle);
        }
    }
}
