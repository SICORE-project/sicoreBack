<?php

namespace Database\Seeders;

use App\Models\Parametrage\Enseignant;
use Illuminate\Database\Seeder;

/**
 * Jeu de données factices pour tester le formulaire de convocation
 * (recherche "Chef de centre" et "Membres du jury"), qui interroge
 * GET /api/enseignants?search=... (EnseignantsController::index).
 *
 * Ne remplit que les colonnes réellement utilisées par ce contrôleur
 * (matricule, nom, prenom, telephone, email) + les colonnes requises
 * par le scope actif()/search() du modèle (est_actif, statut).
 * Toutes les autres colonnes de `enseignants` restent nullable et ne
 * sont pas renseignées ici.
 *
 * Utilisation :
 *   php artisan db:seed --class=EnseignantSeeder
 */
class EnseignantSeeder extends Seeder
{
    public function run(): void
    {
        $prenoms = ['Awa', 'Moussa', 'Fatou', 'Ibrahima', 'Aissatou', 'Mamadou', 'Khady', 'Ousmane', 'Bineta', 'Cheikh', 'Aminata', 'Modou', 'Sokhna', 'Assane', 'Ndeye'];
        $noms    = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Ba', 'Sow', 'Diallo', 'Gueye', 'Faye', 'Cissé', 'Ndour', 'Sy', 'Kane', 'Dieng', 'Thiam'];

        foreach (range(1, 20) as $i) {
            $prenom = $prenoms[array_rand($prenoms)];
            $nom = $noms[array_rand($noms)];

            Enseignant::create([
                'matricule' => 'ENS' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => '7' . random_int(0, 9) . ' ' . random_int(100, 999) . ' ' . random_int(10, 99) . ' ' . random_int(10, 99),
                'email' => strtolower($prenom . '.' . $nom . $i . '@yopmail.com'),
                'genre' => random_int(0, 1) ? 'M' : 'F',
                'statut' => 'en_activite',
                'est_actif' => true,
            ]);
        }
    }
}
