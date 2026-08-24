<?php

namespace Database\Seeders;

use App\Models\Indemnite\ConvocationCentre;
use App\Models\Indemnite\Convocations;
use App\Models\Parametrage\Enseignant;
use Illuminate\Database\Seeder;

/**
 * Un centre d'examen par convocation (10 au total), reparti sur 5 noms de
 * centre reels (chacun couvre donc 2 sessions) — demande utilisatrice :
 * "des centre comme CFP DAKAR CPF Ziguinchor [CFP Saint-Louis] CFP THIES
 * CFP KAOLACK". Chaque nom de centre garde le MEME chef de centre et le
 * MEME president du jury sur ses 2 sessions (realiste : ce sont des postes
 * de responsabilite stables, pas tires au sort a chaque examen), pris dans
 * les 10 premiers enseignants seedes par EnseignantSeeder — les 10
 * suivants restent disponibles pour ConvocationEnseignantSeeder (membres
 * du jury "ordinaires").
 *
 * Cree aussi les metiers couverts par chaque centre (convocation_centre_metiers)
 * dans la foulee : un metier n'a pas de sens sans le centre auquel il
 * appartient, les deux tables sont seedees ensemble ici.
 *
 * Doit etre execute APRES ConvocationSeeder ET EnseignantSeeder.
 *
 * Utilisation : php artisan db:seed --class=ConvocationCentreSeeder
 */
class ConvocationCentreSeeder extends Seeder
{
    private const CENTRES = [
        'CFP Dakar',
        'CFP Ziguinchor',
        'CFP Saint-Louis',
        'CFP Thiès',
        'CFP Kaolack',
    ];

    /**
     * Ville associee a chaque centre — reutilisee comme "provenance" du
     * chef de centre/president du jury, coherent avec le nom du centre.
     */
    private const VILLES = [
        'CFP Dakar' => 'Dakar',
        'CFP Ziguinchor' => 'Ziguinchor',
        'CFP Saint-Louis' => 'Saint-Louis',
        'CFP Thiès' => 'Thiès',
        'CFP Kaolack' => 'Kaolack',
    ];

    private const METIERS = [
        'Couture', 'Cuisine', 'Électricité', 'Habillement', 'Menuiserie',
        'Coiffure', 'Mécanique auto', 'Informatique', 'Plomberie', 'Maçonnerie',
    ];

    public function run(): void
    {
        $convocations = Convocations::orderBy('id')->get();

        // 10 premiers enseignants = pool "direction" (chef de centre +
        // president du jury), 2 par centre — voir ConvocationEnseignantSeeder
        // pour le pool "membres" (les 10 suivants).
        $direction = Enseignant::orderBy('id')->limit(10)->get()->values();

        if ($direction->count() < 10) {
            $this->command?->warn('ConvocationCentreSeeder : moins de 10 enseignants disponibles — exécutez EnseignantSeeder avant.');

            return;
        }

        // Assignation stable centre <-> (chef, president), memoisee ici
        // pour que les 2 sessions d'un meme centre partagent les memes
        // responsables.
        $responsablesParCentre = [];
        foreach (self::CENTRES as $i => $centre) {
            $responsablesParCentre[$centre] = [
                'chef' => $direction[$i * 2],
                'president' => $direction[$i * 2 + 1],
            ];
        }

        foreach ($convocations as $index => $convocation) {
            $nomCentre = self::CENTRES[$index % count(self::CENTRES)];
            $ville = self::VILLES[$nomCentre];
            $responsables = $responsablesParCentre[$nomCentre];
            $chef = $responsables['chef'];
            $president = $responsables['president'];

            // "Lieu d'affectation" — un seul par convocation (voir
            // FraisDeplacementController::lieuAffectation() : "où on l'a
            // affecté pour l'examen", commun à tous les membres). Une
            // convocation sur deux affecte tout le monde SUR PLACE (meme
            // ville que le centre — chef/president/membres y ont deja leur
            // provenance, cf. ci-dessous et ConvocationEnseignantSeeder) :
            // le calcul des frais de deplacement applique alors le taux
            // divise par 4 (calculerMontantDeplacement()). L'autre moitie
            // affecte vers une AUTRE ville : taux plein. Sans cette valeur
            // (laissee null avant ce correctif), la division par 4 n'etait
            // jamais exercee — demande utilisatrice : "le lieu d'affectation
            // n'est pas recupere... et il est important pour les frais de
            // deplacement".
            $memeLieu = $index % 2 === 0;
            $lieuAffectation = $memeLieu ? $ville : self::VILLES[self::CENTRES[($index + 2) % count(self::CENTRES)]];

            $convocation->update(['lieu_affectation' => $lieuAffectation]);

            $centre = ConvocationCentre::create([
                'convocation_id' => $convocation->id,
                'centre' => $nomCentre,
                'jury' => 'Jury '.(($index % 2) + 1),
                'chef_centre_id' => $chef->id,
                'chef_centre_telephone' => $chef->telephone,
                'chef_centre_provenance' => $ville,
                'chef_centre_categorie_personnel' => 'fonctionnaire',
                'president_jury_id' => $president->id,
                'president_jury_telephone' => $president->telephone,
                'president_jury_provenance' => $ville,
                'president_jury_categorie_personnel' => 'fonctionnaire',
            ]);

            // 1 metier pour les centres pairs, 2 pour les impairs — juste
            // assez de variete pour tester le filtre "Métier" sans
            // exploser le volume de donnees.
            $nombreMetiers = $index % 2 === 0 ? 1 : 2;
            $depart = ($index * 2) % count(self::METIERS);

            for ($m = 0; $m < $nombreMetiers; $m++) {
                $centre->metiers()->create([
                    'metier' => self::METIERS[($depart + $m) % count(self::METIERS)],
                ]);
            }
        }
    }
}
