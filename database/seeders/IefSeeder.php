<?php

namespace Database\Seeders;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use Illuminate\Database\Seeder;
use RuntimeException;

class IefSeeder extends Seeder
{
    public function run(): void
    {
        $iefsParIa = [
            // Les trois académies actuelles de la région de Dakar sont encore
            // représentées par IA-DKR dans le référentiel IA de l'application.
            'IA-DKR' => [
                ['code' => 'IEF-DKR-ALM', 'libelle' => 'IEF des Almadies'],
                ['code' => 'IEF-DKR-PLT', 'libelle' => 'IEF de Dakar Plateau'],
                ['code' => 'IEF-DKR-GDK', 'libelle' => 'IEF de Grand Dakar'],
                ['code' => 'IEF-DKR-PAR', 'libelle' => 'IEF des Parcelles Assainies'],
                ['code' => 'IEF-DKR-GDW', 'libelle' => 'IEF de Guédiawaye'],
                ['code' => 'IEF-DKR-KMS', 'libelle' => 'IEF de Keur Massar'],
                ['code' => 'IEF-DKR-PKN', 'libelle' => 'IEF de Pikine'],
                ['code' => 'IEF-DKR-THY', 'libelle' => 'IEF de Thiaroye'],
                ['code' => 'IEF-DKR-DMD', 'libelle' => 'IEF de Diamniadio'],
                ['code' => 'IEF-DKR-RFC', 'libelle' => 'IEF de Rufisque Commune'],
                ['code' => 'IEF-DKR-SGK', 'libelle' => 'IEF de Sangalkam'],
            ],
            'IA-DBL' => [
                ['code' => 'IEF-DBL-BAM', 'libelle' => 'IEF de Bambey'],
                ['code' => 'IEF-DBL-DBL', 'libelle' => 'IEF de Diourbel'],
                ['code' => 'IEF-DBL-MBK', 'libelle' => 'IEF de Mbacké'],
            ],
            'IA-FTK' => [
                ['code' => 'IEF-FTK-DIO', 'libelle' => 'IEF de Diofior'],
                ['code' => 'IEF-FTK-FTK', 'libelle' => 'IEF de Fatick'],
                ['code' => 'IEF-FTK-FDG', 'libelle' => 'IEF de Foundiougne'],
                ['code' => 'IEF-FTK-GOS', 'libelle' => 'IEF de Gossas'],
            ],
            'IA-KFR' => [
                ['code' => 'IEF-KFR-BIR', 'libelle' => 'IEF de Birkelane'],
                ['code' => 'IEF-KFR-KFR', 'libelle' => 'IEF de Kaffrine'],
                ['code' => 'IEF-KFR-KOU', 'libelle' => 'IEF de Koungheul'],
                ['code' => 'IEF-KFR-MHD', 'libelle' => 'IEF de Malem Hodar'],
            ],
            'IA-KLK' => [
                ['code' => 'IEF-KLK-GUI', 'libelle' => 'IEF de Guinguinéo'],
                ['code' => 'IEF-KLK-DEP', 'libelle' => 'IEF de Kaolack Département'],
                ['code' => 'IEF-KLK-NIO', 'libelle' => 'IEF de Nioro'],
            ],
            'IA-KDG' => [
                ['code' => 'IEF-KDG-KDG', 'libelle' => 'IEF de Kédougou'],
                ['code' => 'IEF-KDG-SAL', 'libelle' => 'IEF de Salémata'],
                ['code' => 'IEF-KDG-SAR', 'libelle' => 'IEF de Saraya'],
            ],
            'IA-KLD' => [
                ['code' => 'IEF-KLD-KLD', 'libelle' => 'IEF de Kolda'],
                ['code' => 'IEF-KLD-MYF', 'libelle' => 'IEF de Médina Yoro Foulah'],
                ['code' => 'IEF-KLD-VEL', 'libelle' => 'IEF de Vélingara'],
            ],
            'IA-LGA' => [
                ['code' => 'IEF-LGA-KEB', 'libelle' => 'IEF de Kébémer'],
                ['code' => 'IEF-LGA-LIN', 'libelle' => 'IEF de Linguère'],
                ['code' => 'IEF-LGA-LGA', 'libelle' => 'IEF de Louga'],
            ],
            'IA-MTM' => [
                ['code' => 'IEF-MTM-KAN', 'libelle' => 'IEF de Kanel'],
                ['code' => 'IEF-MTM-MTM', 'libelle' => 'IEF de Matam'],
                ['code' => 'IEF-MTM-RAN', 'libelle' => 'IEF de Ranérou-Ferlo'],
            ],
            'IA-SLS' => [
                ['code' => 'IEF-SLS-DAG', 'libelle' => 'IEF de Dagana'],
                ['code' => 'IEF-SLS-POD', 'libelle' => 'IEF de Podor'],
                ['code' => 'IEF-SLS-COM', 'libelle' => 'IEF de Saint-Louis Commune'],
                ['code' => 'IEF-SLS-DEP', 'libelle' => 'IEF de Saint-Louis Département'],
                ['code' => 'IEF-SLS-PET', 'libelle' => 'IEF de Pété'],
            ],
            'IA-SDH' => [
                ['code' => 'IEF-SDH-BOU', 'libelle' => 'IEF de Bounkiling'],
                ['code' => 'IEF-SDH-GOU', 'libelle' => 'IEF de Goudomp'],
                ['code' => 'IEF-SDH-SDH', 'libelle' => 'IEF de Sédhiou'],
            ],
            'IA-TBA' => [
                ['code' => 'IEF-TBA-BAK', 'libelle' => 'IEF de Bakel'],
                ['code' => 'IEF-TBA-GOU', 'libelle' => 'IEF de Goudiry'],
                ['code' => 'IEF-TBA-KOU', 'libelle' => 'IEF de Koumpentoum'],
                ['code' => 'IEF-TBA-TBA', 'libelle' => 'IEF de Tambacounda'],
            ],
            'IA-THS' => [
                ['code' => 'IEF-THS-MB1', 'libelle' => 'IEF de Mbour 1'],
                ['code' => 'IEF-THS-MB2', 'libelle' => 'IEF de Mbour 2'],
                ['code' => 'IEF-THS-COM', 'libelle' => 'IEF de Thiès Commune'],
                ['code' => 'IEF-THS-DEP', 'libelle' => 'IEF de Thiès Département'],
                ['code' => 'IEF-THS-TIV', 'libelle' => 'IEF de Tivaouane'],
            ],
            'IA-ZGN' => [
                ['code' => 'IEF-ZGN-ZGN', 'libelle' => 'IEF de Ziguinchor'],
                ['code' => 'IEF-ZGN-BG1', 'libelle' => 'IEF de Bignona 1'],
                ['code' => 'IEF-ZGN-BG2', 'libelle' => 'IEF de Bignona 2'],
                ['code' => 'IEF-ZGN-OUS', 'libelle' => 'IEF d’Oussouye'],
            ],
        ];

        foreach ($iefsParIa as $iaCode => $iefs) {
            $ia = Ia::query()->where('code', $iaCode)->first();

            if ($ia === null) {
                throw new RuntimeException("IA introuvable pour le code {$iaCode}. Exécutez IaSeeder avant IefSeeder.");
            }

            foreach ($iefs as $data) {
                $ief = Ief::withTrashed()->updateOrCreate(
                    ['code' => $data['code']],
                    ['libelle' => $data['libelle'], 'ia_id' => $ia->id],
                );

                if ($ief->trashed()) {
                    $ief->restore();
                }
            }
        }
    }
}
