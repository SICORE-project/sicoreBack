<?php

namespace Database\Seeders;

use App\Models\Admin\User;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculationService;
use App\Services\PayrollReferenceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Explicit local demo only. Never included in DatabaseSeeder. */
class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'sicore_local') {
            throw new RuntimeException('Ce jeu de démonstration est réservé à la base locale sicore_local.');
        }
        if (DB::table('payroll_periods')->where('code', '2026-10')->exists()) {
            $this->command?->warn('Octobre 2026 existe déjà : aucune donnée existante n’a été remplacée.');
            return;
        }

        DB::transaction(function (): void {
            $actor = User::where('email', 'mamadou.diop@sicore.sn')->firstOrFail();
            $year = $this->reference('annee_academiques', ['libelle' => '2026-2027'], [
                'date_debut' => '2026-10-01', 'date_fin' => '2027-09-30',
                'est_active' => true, 'est_cloturee' => false,
            ]);
            $pc = $this->reference('statuts_enseignant', ['code' => 'PC'], [
                'libelle' => 'Professeurs contractuels', 'categorie' => 'contractuel',
            ]);
            $vac = $this->reference('statuts_enseignant', ['code' => 'VAC'], [
                'libelle' => 'Vacataires', 'categorie' => 'vacataire',
            ]);
            $thies = DB::table('ias')->where('code', 'IA-THS')->value('id');
            $dakar = DB::table('ias')->where('code', 'IA-DKR')->value('id');
            if (! $thies || ! $dakar) { throw new RuntimeException('Exécuter LocalSetupSeeder avant la démonstration.'); }
            $locations = [];
            foreach ([
                ['IEF-THS-COM', 'Thiès Ville', $thies],
                ['IEF-THS-MB2', 'Mbour 2', $thies],
                ['IEF-DKR-DMD', 'Diamniadio / Rufisque', $dakar],
            ] as [$code, $label, $ia]) {
                $ief = DB::table('iefs')->where('code', $code)->value('id');
                if (! $ief) { throw new RuntimeException('IEF manquante : '.$code); }
                $centre = $this->reference('centres_formation', ['code' => 'DEMO-'.count($locations)], [
                    'nom' => 'Centre de formation Démonstration — '.$label,
                    'ia_id' => $ia, 'ief_id' => $ief, 'est_actif' => true,
                ]);
                $locations[] = compact('ia', 'ief', 'centre');
            }
            $banks = [];
            foreach (['CBAO', 'BHS', 'SGBS'] as $bank) {
                $banks[] = $this->reference('instituts_financieres', ['code' => 'DEMO-'.$bank], [
                    'libelle' => $bank.' — Démonstration', 'sigle' => $bank,
                    'type_institution' => 'banque', 'est_actif' => true,
                ]);
            }

            $labels = [
                'SALAIRE_BASE' => 'Salaire de base', 'PRIME_SPECIALE' => 'Prime spéciale',
                'INDEMNITE_COMPENSATION' => 'Indemnité de compensation', 'IRD' => 'Indemnité de recherche et documentation',
                'IPRES_SALARIE' => 'IPRES — part salariale', 'IPRES_EMPLOYEUR' => 'IPRES — part employeur',
                'IPM' => 'Cotisation IPM', 'IMPR' => 'Impôt mensuel sur le revenu',
                'TRIMF' => 'Minimum fiscal — TRIMF', 'CHECKOFF_UES' => 'Cotisation syndicale — UES',
                'TABASKI_RETENUE' => 'Retenue sur avance Tabaski', 'RAPPEL_GAIN' => 'Rappel de salaire',
                'RETENUE_DEMO' => 'Retenue exceptionnelle de démonstration', 'ABSENCE' => 'Retenue pour absence',
            ];
            foreach (config('payroll_reference.contract_salary_increases', []) as $increase) {
                $labels[$increase['code']] = $increase['label'];
            }
            foreach ($labels as $code => $label) {
                // The administration catalogue currently limits codes to 20 characters.
                // Longer calculation codes keep their original label on the bulletin.
                if (strlen($code) > 20) { continue; }
                $this->reference('rubrique_paies', ['code' => $code], [
                    'libelle' => $label,
                    'type' => in_array($code, ['IPRES_SALARIE', 'IPM', 'IMPR', 'TRIMF', 'CHECKOFF_UES', 'TABASKI_RETENUE', 'RETENUE_DEMO', 'ABSENCE'], true) ? 'retenue' : 'gain',
                    'periodicite' => in_array($code, ['RAPPEL_GAIN', 'RETENUE_DEMO'], true) ? 'ponctuelle' : 'mensuelle',
                    'est_afficher_bulletin' => true, 'est_actif' => true,
                ]);
            }

            $period = PayrollPeriod::create([
                'code' => '2026-10', 'label' => 'Octobre 2026', 'start_date' => '2026-10-01',
                'end_date' => '2026-10-31', 'status' => 'open',
            ]);
            $names = [['Awa', 'Diallo'], ['Moussa', 'Sarr'], ['Fatou', 'Ndiaye'], ['Ibrahima', 'Fall'],
                ['Mariama', 'Ba'], ['Cheikh', 'Sow'], ['Aminata', 'Diop'], ['Oumar', 'Faye'], ['Sokhna', 'Gueye'], ['Aliou', 'Seck']];
            $diplomas = ['BAC_BT', 'CAP', 'BEP', 'BTS_DUEL_DUES', 'LICENCE', 'MASTER_MAITRISE'];
            foreach ($names as $index => [$prenom, $nom]) {
                $isPc = $index < 6;
                $type = $isPc ? PayrollReferenceService::CONTRACTUEL : PayrollReferenceService::VACATAIRE;
                $diploma = $isPc ? $diplomas[$index] : null;
                $category = $isPc ? ($index < 4 ? 1 : 2) : null;
                $location = $locations[$index % 3];
                $matricule = sprintf('DEMO-%s-%03d', $isPc ? 'PC' : 'VAC', $index + 1);
                $account = sprintf('DEMO-COMPTE-%04d', $index + 1);
                $teacher = $this->reference('enseignants', ['matricule' => $matricule], [
                    'prenom' => $prenom, 'nom' => $nom.' (Démo)', 'email' => strtolower($matricule).'@example.test',
                    'genre' => $index % 2 ? 'masculin' : 'feminin', 'statut' => 'en_activite',
                    'statut_enseignant_id' => $isPc ? $pc : $vac, 'type_engagement' => $type,
                    'ia_id' => $location['ia'], 'ief_id' => $location['ief'], 'centre_formation_id' => $location['centre'],
                    'salaire_base' => app(PayrollReferenceService::class)->salaryFor($type, $diploma, $category, '2026-10-31'),
                    'payroll_diploma_level' => $diploma, 'payroll_category_level' => $category,
                    'impr_monthly_amount' => $isPc ? 11767 + $index * 500 : 3000,
                    'trimf_monthly_amount' => 500, 'ipm_monthly_amount' => $isPc ? 4500 : 0,
                    'union_checkoff_monthly_amount' => $isPc ? 1000 : 0,
                    'payroll_profile_configured_at' => now(), 'payroll_profile_configured_by' => $actor->id,
                    'est_actif' => true, 'actif' => true, 'numero_compte' => $account,
                    'numero_compte_bancaire' => $account, 'observations' => 'DONNÉES FICTIVES — démonstration de paie locale.',
                ]);
                $this->reference('comptes_bancaires_enseignants', ['enseignant_id' => $teacher, 'numero_compte' => $account], [
                    'institut_financier_id' => $banks[$index % 3], 'titulaire_compte' => $prenom.' '.$nom.' (Démo)',
                    'est_principal' => true, 'est_actif' => true,
                ]);
                $this->reference('payroll_attendances', ['enseignant_id' => $teacher, 'payroll_period_id' => $period->id], [
                    'absence_days' => $index === 8 ? 1 : 0, 'deduction_amount' => $index === 8 ? 5000 : 0,
                    'status' => 'validated', 'validated_by' => $actor->id, 'validated_at' => now(),
                    'notes' => 'Présence de démonstration — octobre 2026.',
                ]);
                $variables = $index === 0 ? [['TABASKI_RETENUE', 'deduction', 10000]] : match ($index % 4) {
                    1 => [['RAPPEL_GAIN', 'earning', 15000]],
                    2 => [['RETENUE_DEMO', 'deduction', 2500]],
                    default => [],
                };
                foreach ($variables as [$code, $categoryType, $amount]) {
                    $this->reference('payroll_elements', ['enseignant_id' => $teacher, 'payroll_period_id' => $period->id, 'code' => $code], [
                        'label' => $labels[$code], 'category' => $categoryType, 'amount' => $amount,
                        'source' => 'manual', 'status' => 'validated', 'annee_academique_id' => $year,
                        'academic_year' => '2026-2027', 'created_by' => $actor->id,
                        'validated_by' => $actor->id, 'validated_at' => now(),
                    ]);
                }
            }
            app(PayrollCalculationService::class)->calculate($period, $actor);
            $this->command?->info('10 bulletins enregistrés : 6 PC et 4 VAC, octobre 2026.');
        });
    }

    private function reference(string $table, array $key, array $values): int
    {
        $existing = DB::table($table)->where($key)->value('id');
        return $existing ? (int) $existing : DB::table($table)->insertGetId([
            ...$key, ...$values, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
