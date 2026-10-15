<?php

namespace App\Services;

use App\Models\Admin\User;
use App\Models\Parametrage\LieuService;
use App\Services\Administration\Personnel\DrhScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecruitmentImporter
{
    public const HEADERS = ['matricule', 'prenom', 'nom', 'date_naissance', 'ia_id', 'ief_id', 'lieu_service_id'];

    public const LEGACY_HEADERS = ['matricule', 'prenom', 'nom', 'date_naissance', 'type_engagement', 'ia_id', 'ief_id', 'lieu_service_id'];

    public function rows(UploadedFile $file, User $user): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        try {
            $first = fgets($handle);
            if ($first === false) {
                throw ValidationException::withMessages(['file' => 'Le fichier est vide.']);
            }
            $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
            $headers = str_getcsv(ltrim($first, "\xEF\xBB\xBF"), $delimiter, '"', '');
            $headers = array_map(function ($header) {
                $key = Str::slug(trim($header), '_');
                return ['prenoms'=>'prenom', 'date_de_naissance'=>'date_naissance', 'naissance'=>'date_naissance',
                    'type'=>'type_engagement', 'categorie'=>'type_engagement', 'corps'=>'type_engagement',
                    'id_ia'=>'ia_id', 'id_ief'=>'ief_id', 'id_etablissement'=>'lieu_service_id'][$key] ?? $key;
            }, $headers);
            $missing = array_diff(['prenom','nom','date_naissance'], $headers);
            if ($missing) {
                throw ValidationException::withMessages(['file' => 'Colonnes non reconnues ou absentes : '.implode(', ', $missing).'.']);
            }
            if (count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['file' => 'Plusieurs colonnes portent le même nom.']);
            }
            $rows = [];
            $errors = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if ($line > 5001) {
                    throw ValidationException::withMessages(['file' => 'Maximum 5000 recrutés par lot.']);
                }
                if (count($values) !== count($headers)) {
                    $errors["ligne_$line"] = 'Nombre de colonnes incorrect.';

                    continue;
                }
                $source = array_combine($headers, array_map(fn ($v) => trim($v) === '' ? null : trim($v), $values));
                foreach (['ia','ief','etablissement','lieu_service'] as $label) {
                    if (! empty($source[$label])) {
                        $errors["ligne_$line"] = 'Le rattachement « '.$label.' » utilise un nom. Sa correspondance avec le référentiel doit être précisée avant import.';
                        continue 2;
                    }
                }
                $row = array_replace(array_fill_keys(self::HEADERS, null), array_intersect_key($source, array_flip(self::LEGACY_HEADERS)));
                if (! array_key_exists('type_engagement', $row)) $row['type_engagement'] = 'vacataire';
                $v = Validator::make($row, [
                    'matricule' => ['nullable', 'string', 'max:9', 'regex:/^[A-Za-z0-9]+$/', 'unique:enseignants,matricule'],
                    'prenom' => ['required', 'string', 'max:50'], 'nom' => ['required', 'string', 'max:50'],
                    'date_naissance' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->subYears(18)->toDateString()],
                    'type_engagement' => ['required', 'in:vacataire'],
                    'ia_id' => ['nullable', 'integer', 'exists:ias,id'], 'ief_id' => ['nullable', 'integer', 'exists:iefs,id'],
                    'lieu_service_id' => ['nullable', 'integer', 'exists:lieu_de_services,id'],
                ], ['type_engagement.in' => 'Cet import est réservé aux nouveaux enseignants vacataires.']);
                if ($v->fails()) {
                    $errors["ligne_$line"] = $v->errors()->all();

                    continue;
                }
                if ($row['matricule'] && isset($seen[strtolower($row['matricule'])])) {
                    $errors["ligne_$line"] = 'Matricule répété dans le fichier.';

                    continue;
                }
                if ($row['matricule']) {
                    $seen[strtolower($row['matricule'])] = true;
                }
                $identity = mb_strtolower($row['prenom'].'|'.$row['nom'].'|'.$row['date_naissance']);
                if (! $row['matricule'] && isset($seen[$identity])) {
                    $errors["ligne_$line"] = 'Identité répétée sans matricule : vérifiez le fichier.';

                    continue;
                }
                if (! $row['matricule'] && DB::table('enseignants')->whereRaw('LOWER(prenom) = ?', [mb_strtolower($row['prenom'])])->whereRaw('LOWER(nom) = ?', [mb_strtolower($row['nom'])])->where('date_naissance', $row['date_naissance'])->exists()) {
                    $errors["ligne_$line"] = 'Identité déjà présente : renseignez un matricule pour lever une éventuelle homonymie.';

                    continue;
                }
                $seen[$identity] = true;
                if ($row['ief_id'] && ! DB::table('iefs')->where('id', $row['ief_id'])->where('ia_id', $row['ia_id'])->whereNull('deleted_at')->exists()) {
                    $errors["ligne_$line"] = 'IEF incompatible avec l’IA.';

                    continue;
                }
                if ($row['lieu_service_id'] && ! LieuService::whereKey($row['lieu_service_id'])->where('ia_id', $row['ia_id'])->where('ief_id', $row['ief_id'])->where('est_actif', true)->exists()) {
                    $errors["ligne_$line"] = 'Établissement incompatible avec l’IA/IEF.';

                    continue;
                }
                if (! app(DrhScope::class)->allows($user, $row)) {
                    $errors["ligne_$line"] = 'Recruté hors périmètre.';

                    continue;
                }
                $rows[] = $row;
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
            if (! $rows) {
                throw ValidationException::withMessages(['file' => 'Aucun recruté dans le fichier.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
