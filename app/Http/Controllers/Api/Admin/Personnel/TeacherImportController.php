<?php
namespace App\Http\Controllers\Api\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherImportController extends Controller
{
    public function store(Request $request, EnseignantService $service)
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        try {
            $first = fgets($handle);
            if ($first === false) throw ValidationException::withMessages(['csv' => 'Le fichier est vide.']);
            $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            $headers = array_map(fn ($value) => trim(ltrim($value, "\xEF\xBB\xBF")), $headers);
            $required = ['matricule', 'nom', 'prenom', 'date_naissance', 'cni', 'telephone',
                'statut_enseignant_id', 'ia_id', 'ief_id', 'code_banque', 'code_guichet', 'numero_compte', 'cle_rib'];
            if (array_diff($required, $headers) || count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['csv' => 'Les colonnes du fichier doivent respecter le modèle CSV.']);
            }
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($values) === 1 && trim((string) $values[0]) === '') continue;
                if (count($values) !== count($headers)) throw ValidationException::withMessages(['csv' => "Ligne {$line} : nombre de colonnes incorrect."]);
                if (count($rows) >= 500) throw ValidationException::withMessages(['csv' => 'Le maximum est de 500 enseignants par fichier.']);
                $data = array_combine($headers, array_map(fn ($value) => trim((string) $value), $values));
                foreach ($data as $key => $value) if ($value === '') $data[$key] = null;
                foreach (['code_banque', 'code_guichet', 'numero_compte', 'cle_rib'] as $field) {
                    $data['compte_bancaire'][$field] = $data[$field];
                    unset($data[$field]);
                }
                $data['est_actif'] = true;
                $rows[$line] = $data;
            }
            if (!$rows) throw ValidationException::withMessages(['csv' => 'Le fichier ne contient aucun enseignant.']);
            DB::transaction(function () use ($rows, $request, $service) {
                foreach ($rows as $line => $data) {
                    $validation = StoreEnseignantRequest::create('/api/admin/personnel/enseignants', 'POST', $data);
                    $validation->setContainer(app())->setRedirector(app('redirect'));
                    $validation->setUserResolver(fn () => $request->user());
                    try {
                        $validation->validateResolved();
                        $service->create($validation->validated(), $request->user()->id);
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages(['csv' => "Ligne {$line} : ".implode(' ', $exception->validator->errors()->all())]);
                    }
                }
            });
            return response()->json(['message' => count($rows).' enseignant(s) importé(s).', 'count' => count($rows)], 201);
        } finally {
            fclose($handle);
        }
    }
}
