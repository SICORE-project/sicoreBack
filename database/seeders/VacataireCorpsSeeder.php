<?php

namespace Database\Seeders;

use App\Models\Parametrage\CorpsEnseignant;
use Illuminate\Database\Seeder;

class VacataireCorpsSeeder extends Seeder
{
    public function run(): void
    {
        $exists = CorpsEnseignant::query()->where(function ($query) {
            $query->whereRaw('LOWER(TRIM(code)) IN (?, ?)', ['vac', 'vacataire'])
                ->orWhereRaw('LOWER(TRIM(libelle)) = ?', ['vacataire']);
        })->exists();

        if (! $exists) {
            CorpsEnseignant::firstOrCreate(['code' => 'VAC'], ['libelle' => 'Vacataire']);
        }
    }
}
