<?php

namespace App\Services;

use App\Models\categories;
use App\Models\corps_enseignants;
use App\Models\Enseignant;
use App\Models\etablissements;
use App\Models\ias;
use App\Models\iefs;
use App\Models\roles;
use App\Models\TeacherImportBatch;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;
use Throwable;

class ContractTeacherImportService
{
    private const HEADERS = [
        'ia',
        'ief',
        'matricule',
        'prenoms',
        'nom',
        'date_naissance',
        'lieu_naissance',
        'cni',
    ];

    /** @return array<string, mixed> */
    public function execute(
        string $csvPath,
        string $sourceName,
        string $sourceHash,
        string $sourceReference,
        bool $dryRun = false,
        bool $force = false,
    ): array {
        if (! preg_match('/^\d{4}-\d{2}$/', $sourceReference)) {
            throw new RuntimeException('La référence source doit respecter le format AAAA-MM.');
        }

        $rows = $this->readRows($csvPath);
        $analysis = $this->analysis($rows);

        if ($dryRun) {
            return [
                ...$analysis,
                'dry_run' => true,
                'replayed' => false,
            ];
        }

        $previous = TeacherImportBatch::query()
            ->where('source_sha256', $sourceHash)
            ->where('status', 'completed')
            ->first();

        if ($previous && ! $force) {
            return [
                ...(array) $previous->summary,
                'created' => 0,
                'updated' => 0,
                'unchanged' => $previous->source_rows,
                'batch_id' => $previous->id,
                'dry_run' => false,
                'replayed' => true,
            ];
        }

        try {
            return DB::transaction(function () use (
                $rows,
                $analysis,
                $sourceName,
                $sourceHash,
                $sourceReference,
                $previous,
            ): array {
                $batch = $previous ?? new TeacherImportBatch;
                $batch->fill([
                    'source_name' => $sourceName,
                    'source_sha256' => $sourceHash,
                    'source_reference' => $sourceReference,
                    'status' => 'processing',
                    'source_rows' => $rows->count(),
                    'created_rows' => 0,
                    'updated_rows' => 0,
                    'unchanged_rows' => 0,
                    'summary' => null,
                    'error_message' => null,
                    'completed_at' => null,
                ])->save();

                $result = $this->persistRows(
                    $rows,
                    $sourceName,
                    $sourceReference,
                    $analysis
                );
                $batch->update([
                    'status' => 'completed',
                    'created_rows' => $result['created'],
                    'updated_rows' => $result['updated'],
                    'unchanged_rows' => $result['unchanged'],
                    'summary' => $result,
                    'completed_at' => now(),
                ]);

                return [
                    ...$result,
                    'batch_id' => $batch->id,
                    'dry_run' => false,
                    'replayed' => false,
                ];
            }, 3);
        } catch (Throwable $exception) {
            if (! $previous) {
                TeacherImportBatch::query()->updateOrCreate(
                    ['source_sha256' => $sourceHash],
                    [
                        'source_name' => $sourceName,
                        'source_reference' => $sourceReference,
                        'status' => 'failed',
                        'source_rows' => $rows->count(),
                        'error_message' => Str::limit($exception->getMessage(), 60000, ''),
                    ]
                );
            }

            throw $exception;
        }
    }

    /** @return Collection<int, array<string, string>> */
    private function readRows(string $csvPath): Collection
    {
        if (! is_file($csvPath) || ! is_readable($csvPath)) {
            throw new RuntimeException('Le fichier CSV normalisé est introuvable ou illisible.');
        }

        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(';');
        $headers = $file->fgetcsv();
        if (! is_array($headers)) {
            throw new RuntimeException('Le fichier CSV est vide.');
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
        $headers[0] = trim($headers[0], '"');
        $headers = array_map(fn (mixed $header): string => trim((string) $header), $headers);

        if ($headers !== self::HEADERS) {
            throw new RuntimeException(
                'En-têtes CSV invalides. Reçus : '.implode(', ', $headers)
                .'. Colonnes attendues : '.implode(', ', self::HEADERS).'.'
            );
        }

        $rows = collect();
        $seenMatricules = [];
        $seenCnis = [];
        $line = 1;

        while (! $file->eof()) {
            $values = $file->fgetcsv();
            $line++;
            if (! is_array($values) || $values === [null] || $values === [false]) {
                continue;
            }
            if (count($values) !== count(self::HEADERS)) {
                throw new RuntimeException("Nombre de colonnes invalide à la ligne CSV {$line}.");
            }

            $row = array_combine(self::HEADERS, array_map(
                fn (mixed $value): string => trim((string) $value),
                $values
            ));
            if (! $row || implode('', $row) === '') {
                continue;
            }

            foreach (self::HEADERS as $header) {
                if ($row[$header] === '') {
                    throw new RuntimeException("Valeur « {$header} » absente à la ligne CSV {$line}.");
                }
            }

            $row['matricule'] = mb_strtoupper($row['matricule']);
            $row['nom'] = mb_strtoupper($row['nom']);
            $row['cni'] = preg_replace('/\s+/u', '', $row['cni']) ?? $row['cni'];
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $row['date_naissance']);
            if (! $date || $date->format('Y-m-d') !== $row['date_naissance']) {
                throw new RuntimeException(
                    "Date de naissance invalide à la ligne CSV {$line} : {$row['date_naissance']}."
                );
            }
            if ($date > new DateTimeImmutable('today')) {
                throw new RuntimeException("Date de naissance future à la ligne CSV {$line}.");
            }
            if (! preg_match('/^[0-9.\/-]{5,30}$/', $row['cni'])) {
                throw new RuntimeException("CNI invalide à la ligne CSV {$line}.");
            }
            if (isset($seenMatricules[$row['matricule']])) {
                throw new RuntimeException("Matricule dupliqué à la ligne CSV {$line}.");
            }
            if (isset($seenCnis[$row['cni']])) {
                throw new RuntimeException("CNI dupliquée à la ligne CSV {$line}.");
            }

            $seenMatricules[$row['matricule']] = true;
            $seenCnis[$row['cni']] = true;
            $row['_line'] = (string) $line;
            $rows->push($row);
        }

        if ($rows->isEmpty()) {
            throw new RuntimeException('Aucune ligne PC exploitable n’a été trouvée.');
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array<string, string>>  $rows
     * @return array<string, int>
     */
    private function analysis(Collection $rows): array
    {
        return [
            'source_rows' => $rows->count(),
            'distinct_matricules' => $rows->pluck('matricule')->unique()->count(),
            'distinct_cnis' => $rows->pluck('cni')->unique()->count(),
            'cni_format_anomalies' => $rows
                ->filter(fn (array $row): bool => ! preg_match('/^\d{8,20}$/', $row['cni']))
                ->count(),
            'ia_count' => $rows->pluck('ia')->map($this->normalizeKey(...))->unique()->count(),
            'ief_count' => $rows
                ->map(fn (array $row): string => $this->normalizeKey($row['ia']).'|'.$this->normalizeKey($row['ief']))
                ->unique()
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, string>>  $rows
     * @param  array<string, int>  $analysis
     * @return array<string, int>
     */
    private function persistRows(
        Collection $rows,
        string $sourceName,
        string $sourceReference,
        array $analysis,
    ): array {
        $teacherRole = roles::query()->firstOrCreate(['libelle' => 'Enseignant']);
        $category = categories::query()->firstOrCreate(['libelle' => 'Enseignement général']);
        $contractCorps = corps_enseignants::query()->firstOrCreate(
            ['libelle' => 'Professeur contractuel'],
            ['categorie_id' => $category->id]
        );

        $iaCache = ias::query()->get()->keyBy(fn (ias $ia): string => $this->normalizeKey($ia->libelle));
        $iefCache = iefs::query()->get()->keyBy(
            fn (iefs $ief): string => $ief->ia_id.'|'.$this->normalizeKey($ief->libelle)
        );
        $establishmentCache = etablissements::query()
            ->where('code', 'like', 'PC-IEF-%')
            ->get()
            ->keyBy('ief_id');
        $teacherCache = Enseignant::query()
            ->whereNotNull('matricule')
            ->get()
            ->keyBy(fn (Enseignant $teacher): string => mb_strtoupper(trim((string) $teacher->matricule)));
        $usedIaCodes = ias::query()->pluck('code')->map(fn ($code) => mb_strtoupper((string) $code))->all();
        $usedIefCodes = iefs::query()->pluck('code')->map(fn ($code) => mb_strtoupper((string) $code))->all();
        $disabledPassword = Hash::make(Str::random(80));

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $createdIas = 0;
        $createdIefs = 0;
        $createdEstablishments = 0;

        foreach ($rows as $row) {
            $iaKey = $this->normalizeKey($row['ia']);
            $ia = $iaCache->get($iaKey);
            if (! $ia) {
                $ia = ias::query()->create([
                    'code' => $this->uniqueCode('IA', $row['ia'], $usedIaCodes),
                    'libelle' => $this->cleanLabel($row['ia']),
                ]);
                $iaCache->put($iaKey, $ia);
                $createdIas++;
            }

            $iefKey = $ia->id.'|'.$this->normalizeKey($row['ief']);
            $ief = $iefCache->get($iefKey);
            if (! $ief) {
                $ief = iefs::query()->create([
                    'code' => $this->uniqueCode('IEF', $row['ief'], $usedIefCodes),
                    'libelle' => $this->cleanLabel($row['ief']),
                    'ia_id' => $ia->id,
                ]);
                $iefCache->put($iefKey, $ief);
                $createdIefs++;
            }

            $establishment = $establishmentCache->get($ief->id);
            if (! $establishment) {
                $establishment = etablissements::query()->firstOrCreate(
                    ['code' => 'PC-IEF-'.$ief->id],
                    [
                        'libelle' => 'Affectation à préciser — '.$ief->libelle,
                        'ief_id' => $ief->id,
                    ]
                );
                $establishmentCache->put($ief->id, $establishment);
                if ($establishment->wasRecentlyCreated) {
                    $createdEstablishments++;
                }
            }

            $teacher = $teacherCache->get($row['matricule']);
            $teacherWasCreated = ! $teacher;
            $teacher ??= new Enseignant(['matricule' => $row['matricule']]);
            $teacher->fill([
                'cni' => $row['cni'],
                'type_engagement' => 'contractuel',
                'source_import' => $sourceName,
                'source_reference' => $sourceReference,
                'imported_at' => now(),
                'actif' => true,
                'corps_enseignant_id' => $contractCorps->id,
                'etablissement_id' => $establishment->id,
            ]);
            $teacherChanged = $teacherWasCreated || $teacher->isDirty();
            $teacher->save();
            $teacherCache->put($row['matricule'], $teacher);

            $user = $teacher->user;
            $userWasCreated = ! $user;
            if (! $user) {
                $user = new User([
                    'email' => $this->internalEmail($row['matricule']),
                    'password' => $disabledPassword,
                    'login_enabled' => false,
                    'enseignant_id' => $teacher->id,
                    'role_id' => $teacherRole->id,
                ]);
            }
            $user->fill([
                'nom' => $row['nom'],
                'prenom' => $this->cleanLabel($row['prenoms']),
                'date_naiss' => $row['date_naissance'],
                'date_lieu' => $this->cleanLabel($row['lieu_naissance']),
                'role_id' => $user->role_id ?: $teacherRole->id,
                'enseignant_id' => $teacher->id,
            ]);
            $userChanged = $userWasCreated || $user->isDirty();
            $user->save();

            if ($teacherWasCreated) {
                $created++;
            } elseif ($teacherChanged || $userChanged) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        return [
            ...$analysis,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'created_ias' => $createdIas,
            'created_iefs' => $createdIefs,
            'created_establishments' => $createdEstablishments,
        ];
    }

    private function normalizeKey(string $value): string
    {
        return mb_strtoupper(Str::ascii(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value)));
    }

    private function cleanLabel(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    /** @param array<int, string> $usedCodes */
    private function uniqueCode(string $prefix, string $label, array &$usedCodes): string
    {
        $slug = mb_strtoupper(Str::slug(Str::ascii($label), '-'));
        $base = str_starts_with($slug, $prefix.'-') ? $slug : $prefix.'-'.$slug;
        $base = Str::limit($base, 80, '');
        $candidate = $base;
        $suffix = 2;

        while (in_array($candidate, $usedCodes, true)) {
            $candidate = $base.'-'.$suffix++;
        }
        $usedCodes[] = $candidate;

        return $candidate;
    }

    private function internalEmail(string $matricule): string
    {
        $slug = Str::slug(Str::ascii($matricule));

        return 'pc.'.$slug.'.'.substr(hash('sha256', $matricule), 0, 8).'@import.sicore.local';
    }
}
