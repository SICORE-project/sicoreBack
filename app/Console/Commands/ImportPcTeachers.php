<?php

namespace App\Console\Commands;

use App\Services\ContractTeacherImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class ImportPcTeachers extends Command
{
    protected $signature = 'sicore:import-pc
        {file : Fichier source .xls ou CSV normalisé}
        {--period=2026-01 : Référence de la liste au format AAAA-MM}
        {--dry-run : Contrôler le fichier sans modifier la base}
        {--force : Réappliquer un fichier déjà importé}';

    protected $description = 'Importe de manière idempotente la liste des professeurs contractuels actifs';

    public function handle(ContractTeacherImportService $importer): int
    {
        try {
            $source = realpath((string) $this->argument('file'));
            if (! $source || ! is_file($source)) {
                throw new RuntimeException('Le fichier source est introuvable.');
            }

            $hash = hash_file('sha256', $source);
            $csv = $this->normalizedCsv($source, $hash);
            $result = $importer->execute(
                $csv,
                basename($source),
                $hash,
                (string) $this->option('period'),
                (bool) $this->option('dry-run'),
                (bool) $this->option('force'),
            );

            $this->table(['Indicateur', 'Valeur'], [
                ['Lignes source', $result['source_rows']],
                ['Matricules uniques', $result['distinct_matricules']],
                ['CNI uniques', $result['distinct_cnis']],
                ['CNI à contrôler', $result['cni_format_anomalies']],
                ['IA', $result['ia_count']],
                ['IEF', $result['ief_count']],
                ['PC créés', $result['created'] ?? 0],
                ['PC mis à jour', $result['updated'] ?? 0],
                ['PC inchangés', $result['unchanged'] ?? 0],
            ]);

            if ($result['dry_run']) {
                $this->info('Contrôle terminé : aucune donnée n’a été modifiée.');
            } elseif ($result['replayed']) {
                $this->warn('Ce fichier a déjà été importé : aucune nouvelle écriture.');
            } else {
                $this->info('Import des PC terminé avec succès.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function normalizedCsv(string $source, string $hash): string
    {
        $extension = mb_strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            return $source;
        }
        if ($extension !== 'xls') {
            throw new RuntimeException('Formats acceptés : .xls ou .csv.');
        }

        $directory = storage_path('app/imports');
        File::ensureDirectoryExists($directory);
        $destination = $directory.'/pc-'.substr($hash, 0, 16).'.csv';
        $script = base_path('scripts/Convert-PcXlsToCsv.ps1');
        $process = new Process([
            'powershell.exe',
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $script,
            '-SourcePath',
            $source,
            '-DestinationPath',
            $destination,
        ]);
        $process->setTimeout(120);
        $process->mustRun();

        return $destination;
    }
}
