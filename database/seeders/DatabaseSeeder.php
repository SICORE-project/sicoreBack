<?php

namespace Database\Seeders;

use App\Models\roles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- Rôles ---
        $roleAdmin = roles::firstOrCreate(['libelle' => 'Administrateur']);
        $roleGestionnaire = roles::firstOrCreate(['libelle' => 'Gestionnaire']);
        $roleComptable = roles::firstOrCreate(['libelle' => 'Comptable']);
        $roleInspecteur = roles::firstOrCreate(['libelle' => 'Inspecteur']);

        // --- Catégories ---
        $catA = DB::table('categories')->insertGetId(['libelle' => 'Catégorie A', 'created_at' => now(), 'updated_at' => now()]);
        $catB = DB::table('categories')->insertGetId(['libelle' => 'Catégorie B', 'created_at' => now(), 'updated_at' => now()]);
        $catC = DB::table('categories')->insertGetId(['libelle' => 'Catégorie C', 'created_at' => now(), 'updated_at' => now()]);

        // --- Corps enseignants ---
        $corpsPE = DB::table('corps_enseignants')->insertGetId(['libelle' => 'Professeur d\'Enseignement Moyen', 'categorie_id' => $catA, 'created_at' => now(), 'updated_at' => now()]);
        $corpsIE = DB::table('corps_enseignants')->insertGetId(['libelle' => 'Instituteur', 'categorie_id' => $catB, 'created_at' => now(), 'updated_at' => now()]);
        $corpsPES = DB::table('corps_enseignants')->insertGetId(['libelle' => 'Professeur d\'Enseignement Secondaire', 'categorie_id' => $catA, 'created_at' => now(), 'updated_at' => now()]);
        $corpsMI = DB::table('corps_enseignants')->insertGetId(['libelle' => 'Maître d\'Internat', 'categorie_id' => $catC, 'created_at' => now(), 'updated_at' => now()]);

        // --- Diplômes ---
        $dipBac = DB::table('diplomes')->insertGetId(['nom' => 'Baccalauréat', 'created_at' => now(), 'updated_at' => now()]);
        $dipLicence = DB::table('diplomes')->insertGetId(['nom' => 'Licence', 'created_at' => now(), 'updated_at' => now()]);
        $dipMaster = DB::table('diplomes')->insertGetId(['nom' => 'Master', 'created_at' => now(), 'updated_at' => now()]);
        $dipCAP = DB::table('diplomes')->insertGetId(['nom' => 'CAP', 'created_at' => now(), 'updated_at' => now()]);
        $dipCAPES = DB::table('diplomes')->insertGetId(['nom' => 'CAPES', 'created_at' => now(), 'updated_at' => now()]);

        // --- Spécialités ---
        $specMaths = DB::table('specialites')->insertGetId(['nom' => 'Mathématiques', 'created_at' => now(), 'updated_at' => now()]);
        $specFrancais = DB::table('specialites')->insertGetId(['nom' => 'Français', 'created_at' => now(), 'updated_at' => now()]);
        $specSVT = DB::table('specialites')->insertGetId(['nom' => 'Sciences de la Vie et de la Terre', 'created_at' => now(), 'updated_at' => now()]);
        $specPC = DB::table('specialites')->insertGetId(['nom' => 'Physique-Chimie', 'created_at' => now(), 'updated_at' => now()]);
        $specHG = DB::table('specialites')->insertGetId(['nom' => 'Histoire-Géographie', 'created_at' => now(), 'updated_at' => now()]);
        $specAnglais = DB::table('specialites')->insertGetId(['nom' => 'Anglais', 'created_at' => now(), 'updated_at' => now()]);

        // --- Mutuelles ---
        $mutIPRES = DB::table('mutuelles')->insertGetId(['nom' => 'IPRES', 'created_at' => now(), 'updated_at' => now()]);
        $mutCSS = DB::table('mutuelles')->insertGetId(['nom' => 'CSS', 'created_at' => now(), 'updated_at' => now()]);
        $mutFNR = DB::table('mutuelles')->insertGetId(['nom' => 'FNR', 'created_at' => now(), 'updated_at' => now()]);

        // --- Institutions financières ---
        $ifCBCO = DB::table('institution_financieres')->insertGetId(['nom' => 'CBAO', 'adresse' => 'Dakar, Place de l\'Indépendance', 'telephone' => '338392828', 'created_at' => now(), 'updated_at' => now()]);
        $ifBOA = DB::table('institution_financieres')->insertGetId(['nom' => 'BOA Sénégal', 'adresse' => 'Dakar, Avenue Léopold Sédar Senghor', 'telephone' => '338496000', 'created_at' => now(), 'updated_at' => now()]);
        $ifSGBS = DB::table('institution_financieres')->insertGetId(['nom' => 'Société Générale Sénégal', 'adresse' => 'Dakar, Rue de Thann', 'telephone' => '338391616', 'created_at' => now(), 'updated_at' => now()]);
        $ifBICIS = DB::table('institution_financieres')->insertGetId(['nom' => 'BICIS', 'adresse' => 'Dakar, Avenue Léopold Sédar Senghor', 'telephone' => '338390202', 'created_at' => now(), 'updated_at' => now()]);
        $ifCPA = DB::table('institution_financieres')->insertGetId(['nom' => 'La Poste (CCP)', 'adresse' => 'Dakar, Boulevard El Hadj Djily Mbaye', 'telephone' => '338212828', 'created_at' => now(), 'updated_at' => now()]);

        // --- Inspections d'Académie (IA) ---
        $iaDakar = DB::table('ias')->insertGetId(['libelle' => 'IA Dakar', 'code' => 'IA-DK', 'centre_geo' => 'Dakar', 'gouvernance' => 'Région de Dakar', 'created_at' => now(), 'updated_at' => now()]);
        $iaThies = DB::table('ias')->insertGetId(['libelle' => 'IA Thiès', 'code' => 'IA-TH', 'centre_geo' => 'Thiès', 'gouvernance' => 'Région de Thiès', 'created_at' => now(), 'updated_at' => now()]);
        $iaZiguinchor = DB::table('ias')->insertGetId(['libelle' => 'IA Ziguinchor', 'code' => 'IA-ZG', 'centre_geo' => 'Ziguinchor', 'gouvernance' => 'Région de Ziguinchor', 'created_at' => now(), 'updated_at' => now()]);
        $iaSaintLouis = DB::table('ias')->insertGetId(['libelle' => 'IA Saint-Louis', 'code' => 'IA-SL', 'centre_geo' => 'Saint-Louis', 'gouvernance' => 'Région de Saint-Louis', 'created_at' => now(), 'updated_at' => now()]);

        // --- IEF ---
        $iefDakarPlateau = DB::table('iefs')->insertGetId(['libelle' => 'IEF Dakar-Plateau', 'code' => 'IEF-DKP', 'telephone' => '338212020', 'adresse' => 'Dakar Plateau', 'email' => 'ief.dakarplateau@education.sn', 'departement' => 'Dakar', 'ia_id' => $iaDakar, 'created_at' => now(), 'updated_at' => now()]);
        $iefGuedeAwaye = DB::table('iefs')->insertGetId(['libelle' => 'IEF Guédiawaye', 'code' => 'IEF-GDW', 'telephone' => '338372020', 'adresse' => 'Guédiawaye', 'email' => 'ief.guediawaye@education.sn', 'departement' => 'Guédiawaye', 'ia_id' => $iaDakar, 'created_at' => now(), 'updated_at' => now()]);
        $iefThiesVille = DB::table('iefs')->insertGetId(['libelle' => 'IEF Thiès-Ville', 'code' => 'IEF-THV', 'telephone' => '339512020', 'adresse' => 'Thiès centre', 'email' => 'ief.thies@education.sn', 'departement' => 'Thiès', 'ia_id' => $iaThies, 'created_at' => now(), 'updated_at' => now()]);
        $iefZiguinchor = DB::table('iefs')->insertGetId(['libelle' => 'IEF Ziguinchor', 'code' => 'IEF-ZG', 'telephone' => '339912020', 'adresse' => 'Ziguinchor centre', 'email' => 'ief.ziguinchor@education.sn', 'departement' => 'Ziguinchor', 'ia_id' => $iaZiguinchor, 'created_at' => now(), 'updated_at' => now()]);
        $iefSaintLouis = DB::table('iefs')->insertGetId(['libelle' => 'IEF Saint-Louis', 'code' => 'IEF-SL', 'telephone' => '339612020', 'adresse' => 'Saint-Louis centre', 'email' => 'ief.saintlouis@education.sn', 'departement' => 'Saint-Louis', 'ia_id' => $iaSaintLouis, 'created_at' => now(), 'updated_at' => now()]);

        // --- Établissements ---
        DB::table('etablissements')->insert([
            ['libelle' => 'Lycée Lamine Guèye', 'niveau' => 'secondaire', 'code' => 'ETB-LLG', 'email' => 'lycee.laminegueye@education.sn', 'telephone' => '338215050', 'adresse' => 'Dakar Plateau', 'ief_id' => $iefDakarPlateau, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'CEM Parcelles Assainies U14', 'niveau' => 'moyen', 'code' => 'ETB-CPA14', 'email' => 'cem.parcelles14@education.sn', 'telephone' => '338350101', 'adresse' => 'Parcelles Assainies', 'ief_id' => $iefGuedeAwaye, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'École Élémentaire Guédiawaye 1', 'niveau' => 'elementaire', 'code' => 'ETB-EEG1', 'email' => 'ee.guediawaye1@education.sn', 'telephone' => '338372525', 'adresse' => 'Guédiawaye', 'ief_id' => $iefGuedeAwaye, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Lycée Malick Sy', 'niveau' => 'secondaire', 'code' => 'ETB-LMS', 'email' => 'lycee.malicksy@education.sn', 'telephone' => '339513030', 'adresse' => 'Thiès', 'ief_id' => $iefThiesVille, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'CEM Djignabo', 'niveau' => 'moyen', 'code' => 'ETB-CDJ', 'email' => 'cem.djignabo@education.sn', 'telephone' => '339914040', 'adresse' => 'Ziguinchor', 'ief_id' => $iefZiguinchor, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Lycée Charles De Gaulle', 'niveau' => 'secondaire', 'code' => 'ETB-LCDG', 'email' => 'lycee.cdg@education.sn', 'telephone' => '339616060', 'adresse' => 'Saint-Louis', 'ief_id' => $iefSaintLouis, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- Enseignants ---
        $enseignants = [
            ['indice' => '1200', 'date_recrutement' => '2015-10-01', 'situation_matrimoniale' => 'marie', 'specialite' => 'Mathématiques', 'diplome' => 'CAPES', 'corps_enseignant_id' => $corpsPES, 'specialite_id' => $specMaths, 'diplome_id' => $dipCAPES, 'mutuelle_id' => $mutIPRES, 'institution_financiere_id' => $ifCBCO, 'created_at' => now(), 'updated_at' => now()],
            ['indice' => '1050', 'date_recrutement' => '2018-01-15', 'situation_matrimoniale' => 'celibataire_sans_enfant', 'specialite' => 'Français', 'diplome' => 'Licence', 'corps_enseignant_id' => $corpsPE, 'specialite_id' => $specFrancais, 'diplome_id' => $dipLicence, 'mutuelle_id' => $mutCSS, 'institution_financiere_id' => $ifBOA, 'created_at' => now(), 'updated_at' => now()],
            ['indice' => '900', 'date_recrutement' => '2020-03-01', 'situation_matrimoniale' => 'marie', 'specialite' => 'SVT', 'diplome' => 'Master', 'corps_enseignant_id' => $corpsPES, 'specialite_id' => $specSVT, 'diplome_id' => $dipMaster, 'mutuelle_id' => $mutIPRES, 'institution_financiere_id' => $ifSGBS, 'created_at' => now(), 'updated_at' => now()],
            ['indice' => '800', 'date_recrutement' => '2021-10-01', 'situation_matrimoniale' => 'celibataire_sans_enfant', 'specialite' => 'Anglais', 'diplome' => 'Licence', 'corps_enseignant_id' => $corpsPE, 'specialite_id' => $specAnglais, 'diplome_id' => $dipLicence, 'mutuelle_id' => $mutFNR, 'institution_financiere_id' => $ifBICIS, 'created_at' => now(), 'updated_at' => now()],
            ['indice' => '1100', 'date_recrutement' => '2016-10-01', 'situation_matrimoniale' => 'marie', 'specialite' => 'Physique-Chimie', 'diplome' => 'CAPES', 'corps_enseignant_id' => $corpsPES, 'specialite_id' => $specPC, 'diplome_id' => $dipCAPES, 'mutuelle_id' => $mutIPRES, 'institution_financiere_id' => $ifCPA, 'created_at' => now(), 'updated_at' => now()],
            ['indice' => '750', 'date_recrutement' => '2022-01-10', 'situation_matrimoniale' => 'celibataire_sans_enfant', 'specialite' => 'Histoire-Géographie', 'diplome' => 'CAP', 'corps_enseignant_id' => $corpsIE, 'specialite_id' => $specHG, 'diplome_id' => $dipCAP, 'mutuelle_id' => $mutCSS, 'institution_financiere_id' => $ifCBCO, 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach ($enseignants as $ens) {
            DB::table('enseignants')->insert($ens);
        }

        // --- Utilisateurs (users) ---
        User::firstOrCreate(
            ['email' => 'adminsicore@yopmail.com'],
            ['nom' => 'Super', 'prenom' => 'Admin', 'password' => 'password', 'role_id' => $roleAdmin->id]
        );
        User::firstOrCreate(
            ['email' => 'diallo.mamadou@education.sn'],
            ['nom' => 'Diallo', 'prenom' => 'Mamadou', 'password' => 'password', 'genre' => 'masculin', 'date_naiss' => '1985-03-15', 'date_lieu' => 'Dakar', 'role_id' => $roleGestionnaire->id, 'enseignant_id' => 1]
        );
        User::firstOrCreate(
            ['email' => 'ndiaye.fatou@education.sn'],
            ['nom' => 'Ndiaye', 'prenom' => 'Fatou', 'password' => 'password', 'genre' => 'feminin', 'date_naiss' => '1990-07-22', 'date_lieu' => 'Thiès', 'role_id' => $roleComptable->id, 'enseignant_id' => 2]
        );
        User::firstOrCreate(
            ['email' => 'sow.ibrahima@education.sn'],
            ['nom' => 'Sow', 'prenom' => 'Ibrahima', 'password' => 'password', 'genre' => 'masculin', 'date_naiss' => '1978-11-05', 'date_lieu' => 'Saint-Louis', 'role_id' => $roleInspecteur->id, 'enseignant_id' => 3]
        );

        // --- Type indemnités ---
        $tiCorrection = DB::table('type_indemnites')->insertGetId(['libelle' => 'correction', 'prix_unitaire' => 500.00, 'created_at' => now(), 'updated_at' => now()]);
        $tiSurveillance = DB::table('type_indemnites')->insertGetId(['libelle' => 'surveillance', 'prix_unitaire' => 3000.00, 'created_at' => now(), 'updated_at' => now()]);
        $tiJury = DB::table('type_indemnites')->insertGetId(['libelle' => 'jury', 'prix_unitaire' => 15000.00, 'created_at' => now(), 'updated_at' => now()]);
        $tiDeplacement = DB::table('type_indemnites')->insertGetId(['libelle' => 'deplacement', 'prix_unitaire' => 200.00, 'created_at' => now(), 'updated_at' => now()]);

        // --- Structures ---
        $structDPSE = DB::table('structures')->insertGetId(['nom' => 'DPSE', 'description' => 'Direction de la Planification et de la Statistique Éducative', 'created_at' => now(), 'updated_at' => now()]);
        $structDRH = DB::table('structures')->insertGetId(['nom' => 'DRH', 'description' => 'Direction des Ressources Humaines', 'created_at' => now(), 'updated_at' => now()]);
        $structDAF = DB::table('structures')->insertGetId(['nom' => 'DAF', 'description' => 'Direction de l\'Administration et des Finances', 'created_at' => now(), 'updated_at' => now()]);

        // --- Services ---
        $servBudget = DB::table('services')->insertGetId(['structure_id' => $structDAF, 'nom' => 'Service Budget', 'created_at' => now(), 'updated_at' => now()]);
        $servComptabilite = DB::table('services')->insertGetId(['structure_id' => $structDAF, 'nom' => 'Service Comptabilité', 'created_at' => now(), 'updated_at' => now()]);
        $servPaie = DB::table('services')->insertGetId(['structure_id' => $structDRH, 'nom' => 'Service Paie', 'created_at' => now(), 'updated_at' => now()]);
        $servStats = DB::table('services')->insertGetId(['structure_id' => $structDPSE, 'nom' => 'Service Statistiques', 'created_at' => now(), 'updated_at' => now()]);

        // --- Délégations de crédit ---
        $dc1 = DB::table('delegation_credits')->insertGetId([
            'annee_academique' => '2025-2026', 'reference' => 'DC-2026-001', 'objet' => 'Indemnités d\'examen BFEM',
            'date_delegation' => '2026-01-15', 'date_fin' => '2026-06-30', 'structure_id' => $structDAF, 'service_id' => $servBudget,
            'montant_initial' => 50000000.00, 'montant_disponible' => 50000000.00, 'montant_engage' => 15000000.00, 'montant_consomme' => 8000000.00,
            'solde' => 42000000.00, 'statut' => 'Validee', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $dc2 = DB::table('delegation_credits')->insertGetId([
            'annee_academique' => '2025-2026', 'reference' => 'DC-2026-002', 'objet' => 'Indemnités d\'examen BAC',
            'date_delegation' => '2026-02-01', 'date_fin' => '2026-08-31', 'structure_id' => $structDAF, 'service_id' => $servBudget,
            'montant_initial' => 80000000.00, 'montant_disponible' => 80000000.00, 'montant_engage' => 30000000.00, 'montant_consomme' => 12000000.00,
            'solde' => 68000000.00, 'statut' => 'Validee', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $dc3 = DB::table('delegation_credits')->insertGetId([
            'annee_academique' => '2025-2026', 'reference' => 'DC-2026-003', 'objet' => 'Salaires vacataires T1',
            'date_delegation' => '2026-03-10', 'date_fin' => '2026-12-31', 'structure_id' => $structDRH, 'service_id' => $servPaie,
            'montant_initial' => 25000000.00, 'montant_disponible' => 25000000.00, 'montant_engage' => 0, 'montant_consomme' => 0,
            'solde' => 25000000.00, 'statut' => 'En attente', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // --- Paiements salaires ---
        DB::table('paiement_salaires')->insert([
            ['delegation_credit_id' => $dc1, 'nom_agent' => 'Diallo Mamadou', 'mois' => 'Janvier 2026', 'montant' => 450000.00, 'date_paiement' => '2026-01-31', 'created_at' => now(), 'updated_at' => now()],
            ['delegation_credit_id' => $dc1, 'nom_agent' => 'Ndiaye Fatou', 'mois' => 'Janvier 2026', 'montant' => 380000.00, 'date_paiement' => '2026-01-31', 'created_at' => now(), 'updated_at' => now()],
            ['delegation_credit_id' => $dc1, 'nom_agent' => 'Sow Ibrahima', 'mois' => 'Février 2026', 'montant' => 520000.00, 'date_paiement' => '2026-02-28', 'created_at' => now(), 'updated_at' => now()],
            ['delegation_credit_id' => $dc2, 'nom_agent' => 'Ba Aminata', 'mois' => 'Mars 2026', 'montant' => 400000.00, 'date_paiement' => '2026-03-31', 'created_at' => now(), 'updated_at' => now()],
            ['delegation_credit_id' => $dc2, 'nom_agent' => 'Fall Ousmane', 'mois' => 'Mars 2026', 'montant' => 350000.00, 'date_paiement' => '2026-03-31', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- Rubriques bulletins ---
        $rubSalaireBase = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Salaire de base', 'created_at' => now(), 'updated_at' => now()]);
        $rubIndLogement = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Indemnité de logement', 'created_at' => now(), 'updated_at' => now()]);
        $rubIndTransport = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Indemnité de transport', 'created_at' => now(), 'updated_at' => now()]);
        $rubIPRES = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Retenue IPRES', 'created_at' => now(), 'updated_at' => now()]);
        $rubIR = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Impôt sur le revenu', 'created_at' => now(), 'updated_at' => now()]);
        $rubCSS = DB::table('rubrique_bultins')->insertGetId(['libelle' => 'Retenue CSS', 'created_at' => now(), 'updated_at' => now()]);

        // --- Bulletins de paie ---
        $bul1 = DB::table('bultins')->insertGetId([
            'matricule' => 'MAT-2026-001', 'mois_validite' => '2026-06-01', 'date_enregistrement' => '2026-06-25',
            'numero_ordre' => 'BUL-001', 'net_a_payer' => 485000.00, 'enseignant_id' => 1, 'ia_id' => $iaDakar,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $bul2 = DB::table('bultins')->insertGetId([
            'matricule' => 'MAT-2026-002', 'mois_validite' => '2026-06-01', 'date_enregistrement' => '2026-06-25',
            'numero_ordre' => 'BUL-002', 'net_a_payer' => 380000.00, 'enseignant_id' => 2, 'ia_id' => $iaDakar,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $bul3 = DB::table('bultins')->insertGetId([
            'matricule' => 'MAT-2026-003', 'mois_validite' => '2026-06-01', 'date_enregistrement' => '2026-06-25',
            'numero_ordre' => 'BUL-003', 'net_a_payer' => 520000.00, 'enseignant_id' => 3, 'ia_id' => $iaThies,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // --- Détails bulletins ---
        DB::table('detail_bultins')->insert([
            ['montant_gains' => 350000.00, 'montant_retenus' => 0, 'bultin_id' => $bul1, 'rubrique_bultin_id' => $rubSalaireBase, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 80000.00, 'montant_retenus' => 0, 'bultin_id' => $bul1, 'rubrique_bultin_id' => $rubIndLogement, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 30000.00, 'montant_retenus' => 0, 'bultin_id' => $bul1, 'rubrique_bultin_id' => $rubIndTransport, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 0, 'montant_retenus' => 25000.00, 'bultin_id' => $bul1, 'rubrique_bultin_id' => $rubIPRES, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 0, 'montant_retenus' => 42000.00, 'bultin_id' => $bul1, 'rubrique_bultin_id' => $rubIR, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 300000.00, 'montant_retenus' => 0, 'bultin_id' => $bul2, 'rubrique_bultin_id' => $rubSalaireBase, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 60000.00, 'montant_retenus' => 0, 'bultin_id' => $bul2, 'rubrique_bultin_id' => $rubIndLogement, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 0, 'montant_retenus' => 20000.00, 'bultin_id' => $bul2, 'rubrique_bultin_id' => $rubIPRES, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 400000.00, 'montant_retenus' => 0, 'bultin_id' => $bul3, 'rubrique_bultin_id' => $rubSalaireBase, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 100000.00, 'montant_retenus' => 0, 'bultin_id' => $bul3, 'rubrique_bultin_id' => $rubIndLogement, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 0, 'montant_retenus' => 30000.00, 'bultin_id' => $bul3, 'rubrique_bultin_id' => $rubIPRES, 'created_at' => now(), 'updated_at' => now()],
            ['montant_gains' => 0, 'montant_retenus' => 50000.00, 'bultin_id' => $bul3, 'rubrique_bultin_id' => $rubIR, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- Convocations ---
        $conv1 = DB::table('convocations')->insertGetId([
            'date_emission' => '2026-05-15', 'statut' => 'validee', 'lieu_examen' => 'Lycée Lamine Guèye, Dakar',
            'ordre_de_mission' => true, 'lieu_affectation' => 'Dakar', 'user_id' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $conv2 = DB::table('convocations')->insertGetId([
            'date_emission' => '2026-05-20', 'statut' => 'validee', 'lieu_examen' => 'Lycée Malick Sy, Thiès',
            'ordre_de_mission' => true, 'lieu_affectation' => 'Thiès', 'user_id' => 3, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('convocations')->insert([
            'date_emission' => '2026-06-01', 'statut' => 'en_attente', 'lieu_examen' => 'CEM Djignabo, Ziguinchor',
            'ordre_de_mission' => false, 'lieu_affectation' => null, 'user_id' => 4, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // --- Indemnités ---
        DB::table('indemnites')->insert([
            ['montant' => 150000.00, 'nombre_copies' => 300, 'ordre_de_mission' => false, 'lieu_affectation' => null, 'indice' => null, 'nombre_heures' => null, 'nombre_kilometrages' => null, 'user_id' => 2, 'type_indemnite_id' => $tiCorrection, 'created_at' => now(), 'updated_at' => now()],
            ['montant' => 45000.00, 'nombre_copies' => null, 'ordre_de_mission' => true, 'lieu_affectation' => 'Dakar', 'indice' => null, 'nombre_heures' => 15, 'nombre_kilometrages' => null, 'user_id' => 2, 'type_indemnite_id' => $tiSurveillance, 'created_at' => now(), 'updated_at' => now()],
            ['montant' => 15000.00, 'nombre_copies' => null, 'ordre_de_mission' => true, 'lieu_affectation' => 'Thiès', 'indice' => null, 'nombre_heures' => null, 'nombre_kilometrages' => null, 'user_id' => 3, 'type_indemnite_id' => $tiJury, 'created_at' => now(), 'updated_at' => now()],
            ['montant' => 24000.00, 'nombre_copies' => null, 'ordre_de_mission' => true, 'lieu_affectation' => 'Ziguinchor', 'indice' => null, 'nombre_heures' => null, 'nombre_kilometrages' => 120, 'user_id' => 3, 'type_indemnite_id' => $tiDeplacement, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- États de présence ---
        DB::table('etats_presences')->insert([
            ['nombre_jour' => 22, 'enseignant_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre_jour' => 20, 'enseignant_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre_jour' => 18, 'enseignant_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre_jour' => 22, 'enseignant_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre_jour' => 21, 'enseignant_id' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- États salaires ---
        DB::table('etat_salaires')->insert([
            ['libelle' => 'État salaires Juin 2026 - Dakar', 'annee_academique' => '2025-2026', 'periode' => 'Juin 2026', 'signature' => null, 'ia_id' => $iaDakar, 'ief_id' => $iefDakarPlateau, 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'État salaires Juin 2026 - Thiès', 'annee_academique' => '2025-2026', 'periode' => 'Juin 2026', 'signature' => null, 'ia_id' => $iaThies, 'ief_id' => $iefThiesVille, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- Pièces justificatives ---
        DB::table('piece_justificatives')->insert([
            ['type' => 'Ordre de mission', 'document_url' => '/documents/om-conv1.pdf', 'date_depot' => '2026-05-18', 'statut' => 'valide', 'dossier_complet' => true, 'convocation_id' => $conv1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Attestation de service', 'document_url' => '/documents/att-conv1.pdf', 'date_depot' => '2026-05-18', 'statut' => 'valide', 'dossier_complet' => true, 'convocation_id' => $conv1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Ordre de mission', 'document_url' => '/documents/om-conv2.pdf', 'date_depot' => '2026-05-25', 'statut' => 'en_attente', 'dossier_complet' => false, 'convocation_id' => $conv2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // --- État paie indemnités ---
        DB::table('etat_paie_indemnites')->insert([
            ['date_generation' => '2026-06-30', 'total_montant' => 195000.00, 'lieu_examen' => 'Dakar', 'transmit_sica' => true, 'user_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['date_generation' => '2026-06-30', 'total_montant' => 39000.00, 'lieu_examen' => 'Thiès', 'transmit_sica' => false, 'user_id' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
