<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parametrage\Ia;

class IaSeeder extends Seeder
{
    public function run(): void
    {
        $ias = [
            [
                'code' => 'IA-DKR',
                'libelle' => 'Inspection d\'Académie de Dakar',
                'region_id' => 'DK',
                'departement_id' => null,
                'adresse' => 'Avenue Cheikh Anta Diop, Dakar',
                'telephone' => '338600001',
                'email' => 'ia.dakar@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-THS',
                'libelle' => 'Inspection d\'Académie de Thiès',
                'region_id' => 'TH',
                'departement_id' => null,
                'adresse' => 'Thiès',
                'telephone' => '338600002',
                'email' => 'ia.thies@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-DBB',
                'libelle' => 'Inspection d\'Académie de Diourbel',
                'region_id' => 'DB',
                'departement_id' => null,
                'adresse' => 'Diourbel',
                'telephone' => '338600003',
                'email' => 'ia.diourbel@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-FTK',
                'libelle' => 'Inspection d\'Académie de Fatick',
                'region_id' => 'FK',
                'departement_id' => null,
                'adresse' => 'Fatick',
                'telephone' => '338600004',
                'email' => 'ia.fatick@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-KLK',
                'libelle' => 'Inspection d\'Académie de Kaolack',
                'region_id' => 'KL',
                'departement_id' => null,
                'adresse' => 'Kaolack',
                'telephone' => '338600005',
                'email' => 'ia.kaolack@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-KLD',
                'libelle' => 'Inspection d\'Académie de Kaffrine',
                'region_id' => 'KF',
                'departement_id' => null,
                'adresse' => 'Kaffrine',
                'telephone' => '338600006',
                'email' => 'ia.kaffrine@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-KLG',
                'libelle' => 'Inspection d\'Académie de Kolda',
                'region_id' => 'KD',
                'departement_id' => null,
                'adresse' => 'Kolda',
                'telephone' => '338600007',
                'email' => 'ia.kolda@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-SDR',
                'libelle' => 'Inspection d\'Académie de Sédhiou',
                'region_id' => 'SD',
                'departement_id' => null,
                'adresse' => 'Sédhiou',
                'telephone' => '338600008',
                'email' => 'ia.sedhiou@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-ZGN',
                'libelle' => 'Inspection d\'Académie de Ziguinchor',
                'region_id' => 'ZG',
                'departement_id' => null,
                'adresse' => 'Ziguinchor',
                'telephone' => '338600009',
                'email' => 'ia.ziguinchor@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-TBA',
                'libelle' => 'Inspection d\'Académie de Tambacounda',
                'region_id' => 'TB',
                'departement_id' => null,
                'adresse' => 'Tambacounda',
                'telephone' => '338600010',
                'email' => 'ia.tambacounda@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-KDG',
                'libelle' => 'Inspection d\'Académie de Kédougou',
                'region_id' => 'KG',
                'departement_id' => null,
                'adresse' => 'Kédougou',
                'telephone' => '338600011',
                'email' => 'ia.kedougou@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-LGA',
                'libelle' => 'Inspection d\'Académie de Louga',
                'region_id' => 'LG',
                'departement_id' => null,
                'adresse' => 'Louga',
                'telephone' => '338600012',
                'email' => 'ia.louga@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-MTM',
                'libelle' => 'Inspection d\'Académie de Matam',
                'region_id' => 'MT',
                'departement_id' => null,
                'adresse' => 'Matam',
                'telephone' => '338600013',
                'email' => 'ia.matam@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
            [
                'code' => 'IA-SLS',
                'libelle' => 'Inspection d\'Académie de Saint-Louis',
                'region_id' => 'SL',
                'departement_id' => null,
                'adresse' => 'Saint-Louis',
                'telephone' => '338600014',
                'email' => 'ia.saintlouis@education.sn',
                'responsable' => null,
                'est_actif' => true,
            ],
        ];

        foreach ($ias as $ia) {
            Ia::withTrashed()->updateOrCreate(
                ['code' => $ia['code']],
                $ia + ['deleted_at' => null]
            );
        }
    }
}
