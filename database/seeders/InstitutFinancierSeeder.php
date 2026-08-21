<?php

namespace Database\Seeders;

use App\Models\Parametrage\InstitutFinancier;
use Illuminate\Database\Seeder;

class InstitutFinancierSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            ['code' => 'IF001', 'libelle' => 'Banque de l’Habitat du Sénégal', 'sigle' => 'BHS', 'type_institution' => 'Banque', 'telephone' => '+221 33 839 33 33', 'email' => 'contact@bhs.sn', 'adresse' => 'Boulevard Général-de-Gaulle, Dakar', 'est_actif' => true],
            ['code' => 'IF002', 'libelle' => 'Banque Internationale pour le Commerce et l’Industrie du Sénégal', 'sigle' => 'BICIS', 'type_institution' => 'Banque', 'telephone' => '+221 33 839 03 90', 'email' => 'contact@bicis.sn', 'adresse' => 'Avenue Léopold-Sédar-Senghor, Dakar', 'est_actif' => true],
            ['code' => 'IF003', 'libelle' => 'Caisse Nationale de Crédit Agricole du Sénégal', 'sigle' => 'CNCAS', 'type_institution' => 'Banque', 'telephone' => '+221 33 839 36 36', 'email' => 'contact@cncas.sn', 'adresse' => 'Place de l’Indépendance, Dakar', 'est_actif' => true],
            ['code' => 'IF004', 'libelle' => 'La Banque Agricole', 'sigle' => 'LBA', 'type_institution' => 'Banque', 'telephone' => '+221 33 849 20 20', 'email' => 'relationclient@lba.sn', 'adresse' => 'Route des Almadies, Dakar', 'est_actif' => true],
            ['code' => 'IF005', 'libelle' => 'Crédit Mutuel du Sénégal', 'sigle' => 'CMS', 'type_institution' => 'Système financier décentralisé', 'telephone' => '+221 33 869 48 48', 'email' => 'contact@cms.sn', 'adresse' => 'Avenue Bourguiba, Dakar', 'est_actif' => true],
            ['code' => 'IF006', 'libelle' => 'Institution Mutualiste Communautaire d’Épargne et de Crédit', 'sigle' => 'IMCEC', 'type_institution' => 'Microfinance', 'telephone' => '+221 33 825 12 12', 'email' => 'contact@imcec.sn', 'adresse' => 'Grand-Yoff, Dakar', 'est_actif' => false],
        ];

        foreach ($institutions as $institution) {
            InstitutFinancier::updateOrCreate(
                ['code' => $institution['code']],
                $institution,
            );
        }
    }
}
