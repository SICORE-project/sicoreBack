<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 10 convocations de démonstration, réparties sur 5 sessions (Juin à
 * Octobre 2026) avec une progression de statut cohérente dans le temps :
 * les sessions les plus anciennes sont envoyées, les plus récentes
 * restent brouillon/émise — comme un vrai calendrier d'examens en cours
 * de traitement, pas un jeu de données figé au hasard.
 *
 * Doit être exécuté APRÈS EnseignantSeeder (aucune dépendance directe ici,
 * mais ConvocationCentreSeeder/ConvocationEnseignantSeeder qui suivent en
 * ont besoin) — voir IndemnitesDemoSeeder pour l'ordre complet.
 *
 * Utilisation : php artisan db:seed --class=ConvocationSeeder
 */
class ConvocationSeeder extends Seeder
{
    /**
     * 8 intitulés réalistes de session d'examen/formation en CFP — cyclés
     * sur les 10 convocations pour varier sans répéter deux fois de suite.
     */
    private const OBJETS = [
        "Examen du Certificat d'Aptitude Professionnelle (CAP)",
        'Certification en Brevet de Technicien (BT)',
        'Examen de fin de formation professionnelle',
        "Concours d'entrée en formation qualifiante",
        'Certification en Brevet de Technicien Supérieur (BTS)',
        'Examen de qualification professionnelle',
        'Session de rattrapage — Certificat de spécialisation',
        'Évaluation finale — Formation courte durée',
    ];

    /**
     * (mois, annee, jour de debut) — determine la session ET, via l'ordre
     * chronologique, le statut coherent attribue plus bas (les sessions
     * passees sont deja traitees, les futures restent en preparation).
     */
    private const SESSIONS = [
        ['label' => 'Juin 2026', 'debut' => '2026-06-15'],
        ['label' => 'Juin 2026', 'debut' => '2026-06-22'],
        ['label' => 'Juillet 2026', 'debut' => '2026-07-06'],
        ['label' => 'Juillet 2026', 'debut' => '2026-07-20'],
        ['label' => 'Août 2026', 'debut' => '2026-08-10'],
        ['label' => 'Août 2026', 'debut' => '2026-08-24'],
        ['label' => 'Septembre 2026', 'debut' => '2026-09-07'],
        ['label' => 'Septembre 2026', 'debut' => '2026-09-21'],
        ['label' => 'Octobre 2026', 'debut' => '2026-10-05'],
        ['label' => 'Octobre 2026', 'debut' => '2026-10-19'],
    ];

    /**
     * Statut par index de session (0 = la plus ancienne, 9 = la plus
     * recente) : progression realiste envoyee -> emise -> brouillon au
     * fil du calendrier.
     */
    private const STATUTS = [
        'envoyee', 'envoyee',
        'envoyee', 'envoyee',
        'envoyee', 'emise',
        'emise', 'emise',
        'brouillon', 'brouillon',
    ];

    public function run(): void
    {
        // "Jury d'examen / certification" (voir types_convocation, deja
        // seede par la migration) : le seul type coherent avec ces 10
        // convocations, toutes des sessions d'examen/certification.
        $typeConvocationId = 1;
        $utilisateurId = 1;

        foreach (self::SESSIONS as $index => $session) {
            $dateDebut = Carbon::parse($session['debut']);
            $dateFin = $dateDebut->copy()->addDays(random_int(2, 4));
            $dateEmission = $dateDebut->copy()->subDays(random_int(5, 12));

            Convocations::create([
                'type_convocation_id' => $typeConvocationId,
                'date_emission' => $dateEmission,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'heure_debut' => '08:00:00',
                'objet' => self::OBJETS[$index % count(self::OBJETS)],
                'session' => $session['label'],
                'ordre_de_mission' => true,
                // Renseigne ensuite par ConvocationCentreSeeder, qui
                // connait deja la ville du centre attribue a cette
                // convocation (necessaire pour le calcul des frais de
                // deplacement — voir son commentaire pour le detail).
                'lieu_affectation' => null,
                'statut' => self::STATUTS[$index],
                'utilisateur_id' => $utilisateurId,
            ]);
        }
    }
}
