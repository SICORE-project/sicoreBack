<?php

namespace Database\Seeders;

use App\Models\Parametrage\Categorie;
use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GestionPaieSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = Categorie::query()->firstOrCreate(
                ['code' => 'PAIE'],
                [
                    'libelle' => 'Personnel enseignant',
                    'ordre' => 10,
                    'description' => 'Référentiel minimal utilisé par la gestion de la paie.',
                ]
            );

            foreach ([
                'VAC' => 'Vacataires',
                'PC' => 'Professeurs contractuels',
            ] as $code => $label) {
                $existing = DB::table('corps_enseignant')
                    ->where('code', $code)
                    ->orWhere('libelle', $label)
                    ->first();

                if ($existing) {
                    DB::table('corps_enseignant')->where('id', $existing->id)->update([
                        'code' => $code,
                        'libelle' => $label,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('corps_enseignant')->insert([
                        'code' => $code,
                        'libelle' => $label,
                        'categorie_id' => $category->id,
                        'description' => 'Corps disponible pour les traitements de paie Tabaski.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $academicYear = DB::table('annee_academiques')->where('libelle', '2025-2026')->first();
            if (! $academicYear) {
                $academicYearId = DB::table('annee_academiques')->insertGetId([
                    'libelle' => '2025-2026',
                    'date_debut' => '2025-10-01',
                    'date_fin' => '2026-09-30',
                    'en cours' => true,
                    'est_cloturee' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $academicYear = DB::table('annee_academiques')->find($academicYearId);
            }

            $cursor = CarbonImmutable::parse($academicYear->date_debut)->startOfMonth();
            $end = CarbonImmutable::parse($academicYear->date_fin)->endOfMonth();
            while ($cursor->lessThanOrEqualTo($end)) {
                PayrollPeriod::query()->firstOrCreate(
                    ['code' => $cursor->format('Y-m')],
                    [
                        'label' => ucfirst($cursor->locale('fr')->translatedFormat('F Y')),
                        'start_date' => $cursor->startOfMonth()->toDateString(),
                        'end_date' => $cursor->endOfMonth()->toDateString(),
                        'status' => PayrollPeriod::STATUS_OPEN,
                    ]
                );
                $cursor = $cursor->addMonth();
            }
        });
    }
}
