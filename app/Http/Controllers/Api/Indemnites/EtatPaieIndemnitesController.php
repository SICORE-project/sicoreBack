<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\UpdateEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\GenererEtatPaieIndemniteRequest;
use App\Models\Indemnite\Etat_paie_indemnites;
use App\Models\indemnites;
use App\Models\Parametrage\Enseignant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EtatPaieIndemnitesController extends Controller
{
    use ApiResponseTrait;

    /**
     * États de paie déjà créés correspondant au filtre courant de la page
     * (type + objet/session/centre) — demande utilisatrice : "ajouter un
     * bouton voir etat qui affiche les informations en modal". 'objet'
     * n'est pas une colonne propre (seulement dans 'perimetre', du JSON) ;
     * type/session/lieu_examen(centre) le sont.
     */
    public function index(Request $request)
    {
        $query = Etat_paie_indemnites::query();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('session')) {
            $query->where('session', $request->query('session'));
        }

        if ($request->filled('centre')) {
            $query->where('lieu_examen', $request->query('centre'));
        }

        if ($request->filled('objet')) {
            $query->where('perimetre->objet', $request->query('objet'));
        }

        $etatsPaie = $query->latest('id')->get();

        return $this->success('États de paie correspondants.', $etatsPaie);
    }

    /**
     * Crée une fiche état de paie regroupant les membres actuellement
     * affichés (type + objet/session/centre) sur la page front — demande
     * utilisatrice : "mets un bouton ajouter etat de paie". 'details' (liste
     * des membres avec montant) et 'total_montant' ne sont pas dans
     * StoreEtatPaieIndemniteRequest (formulaire construit ligne par ligne
     * côté front, pas par l'utilisatrice elle-même) : lus directement sur
     * la requête plutôt que validés un par un.
     */
    public function store(StoreEtatPaieIndemniteRequest $request)
    {
        $data = $request->validated();
        $details = $request->input('details', []);

        $etatPaie = Etat_paie_indemnites::create([
            'reference' => 'EP-'.strtoupper(Str::random(8)),
            'type' => $data['type'],
            'utilisateur_id' => $request->user()?->id,
            'date_generation' => now(),
            'periode_debut' => $data['periode_debut'],
            'periode_fin' => $data['periode_fin'],
            'lieu_examen' => $data['lieu_examen'] ?? null,
            'session' => $data['session'] ?? null,
            'perimetre' => $data['perimetre'] ?? null,
            'details' => $details,
            'total_montant' => collect($details)->sum('montant'),
            'statut' => 'brouillon',
        ]);

        return $this->success('État de paie créé avec succès.', $etatPaie, 201);
    }

    /**
     * Génère la fiche PDF "État de paiement examens" (contrôle clé RIB /
     * fichier virement de masse Trésor Public) directement à partir du
     * filtre courant côté front — demande utilisatrice : "je voudrais a
     * chaque filtre generer un fiche d'etat de paie le fichier doit avoir
     * ses informations" (photo du document papier officiel fournie). Ad hoc :
     * ne nécessite pas qu'un état de paie ait déjà été enregistré en base
     * (contrairement à index()/store() ci-dessus).
     */
    public function genererFichePdf(Request $request)
    {
        $details = $request->input('details', []);

        if (empty($details)) {
            return $this->error('Aucun membre à inclure dans la fiche.', 422);
        }

        $enseignantIds = collect($details)->pluck('id')->filter()->unique()->values();

        $ribParEnseignant = Enseignant::whereIn('id', $enseignantIds)
            ->get(['id', 'code_banque', 'code_guichet', 'numero_compte_bancaire', 'cle_rib'])
            ->keyBy('id');

        // Demande utilisatrice : "la generation doit dependre du donner du
        // type d'indemnite" — le RIB deja saisi n'est reutilise que depuis
        // des etats de paie du MEME type (indemnite_correction, frais_deplacement...),
        // pas mélangé entre types.
        [$ribDejaSaisiParId, $ribDejaSaisiParNom] = $this->ribDejaSaisi($request->input('type'));

        $session = $request->input('session');
        $typeLibelle = $request->input('type_libelle') ?: 'Indemnité';
        $libelleGeneral = $request->input('libelle_operation') ?: trim($typeLibelle.' '.($session ?? ''));

        $lignes = collect($details)->map(function (array $ligne) use ($ribParEnseignant, $ribDejaSaisiParId, $ribDejaSaisiParNom, $libelleGeneral) {
            // Priorité : RIB déjà saisi à la main sur un état de paie
            // existant (voir "Ajouter état de paie") > fiche permanente de
            // l'enseignant > vide — demande utilisatrice : "tu n'a pas
            // recupere les valeur" (RIB déjà entré ailleurs, jamais relu ici).
            $rib = null;

            if (! empty($ligne['id'])) {
                $rib = $ribDejaSaisiParId[(string) $ligne['id']] ?? null;
            }

            if (! $rib) {
                $cle = Str::lower(trim(($ligne['nom'] ?? '').'|'.($ligne['prenom'] ?? '')));
                $rib = $ribDejaSaisiParNom[$cle] ?? null;
            }

            $ribProfil = ! empty($ligne['id']) ? $ribParEnseignant->get($ligne['id']) : null;

            return [
                'nom' => $ligne['nom'] ?? null,
                'prenom' => $ligne['prenom'] ?? null,
                'metier' => $ligne['metier'] ?? null,
                'fonction' => $ligne['fonction'] ?? null,
                'montant' => (float) ($ligne['montant'] ?? 0),
                // Le libellé décrit le TYPE d'opération pour CE membre —
                // Correction/Surveillance/Chef de centre/Président de jury
                // (sa 'fonction'), pas le métier : le métier est déjà
                // l'en-tête du tableau qui regroupe la ligne (voir $groupes
                // plus bas), le répéter ici ferait doublon — demande
                // utilisatrice : "tu dois recuperer ci c'est correction ou
                // surveillant ou president de jury ou president de centre".
                'libelle' => $ligne['fonction'] ?? $libelleGeneral,
                'code_banque' => $ligne['code_banque'] ?? $rib['code_banque'] ?? $ribProfil?->code_banque,
                'code_guichet' => $ligne['code_guichet'] ?? $rib['code_guichet'] ?? $ribProfil?->code_guichet,
                'numero_compte_bancaire' => $ligne['numero_compte_bancaire'] ?? $rib['numero_compte_bancaire'] ?? $ribProfil?->numero_compte_bancaire,
                'cle_rib' => $ligne['cle_rib'] ?? $rib['cle_rib'] ?? $ribProfil?->cle_rib,
            ];
        });

        // Un libellé de groupe pour CHAQUE ligne (metier si connu, sinon
        // fonction) — evite le tableau "orphelin" sans en-tete repere quand
        // plusieurs membres (chef de centre, president de jury...) n'ont pas
        // de metier (voir point mineur signale par l'utilisatrice).
        $groupes = $lignes
            ->groupBy(fn (array $ligne) => $ligne['metier'] ?: ($ligne['fonction'] ?: 'Autres'))
            ->map(fn ($groupe, $libelleGroupe) => ['metier' => $libelleGroupe, 'lignes' => $groupe->values()])
            ->values();

        $annee = null;
        if ($session && preg_match('/\d{4}/', $session, $m)) {
            $annee = $m[0];
        }
        $annee ??= now()->year;

        $objet = $request->input('objet');

        // "EXAMENS 2026 doit etre le l'objet de la convocation" — le titre
        // reprend l'objet de la convocation plutot qu'un intitule generique
        // "EXAMENS", avec repli sur "EXAMENS" si l'objet est absent.
        $titre = trim(($objet ?: 'Examens').' '.$annee);

        // Centre filtré côté front s'il y en a un, sinon les centres
        // distincts des membres inclus (filtre "tous centres" confondus) —
        // demande utilisatrice : "mets le nom du centre sur l'entete du
        // fichier".
        $centre = $request->input('centre') ?: collect($details)->pluck('centre')->filter()->unique()->implode(', ');

        $pdf = Pdf::loadView('pdf.etat-paie', [
            'reference' => $request->input('reference'),
            'dateGeneration' => now()->translatedFormat('d/m/Y'),
            'titre' => $titre,
            'centre' => $centre ?: null,
            'groupes' => $groupes,
            'totalMontant' => $lignes->sum('montant'),
            'nombreLignes' => $lignes->count(),
        ])->setPaper('a4', 'landscape');

        // Affichage dans le navigateur avant telechargement (demande
        // utilisatrice : "afficher le fichier pdf avant de le telecharger")
        // — stream() = Content-Disposition inline, contrairement a
        // download() (attachment) utilise ailleurs (FraisDeplacementPdfController).
        return $pdf->stream('etat-de-paiement.pdf');
    }

    /**
     * RIB déjà saisis à la main sur des états de paie existants (via
     * "Ajouter état de paie") — permet à genererFichePdf() de les réutiliser
     * pour un membre qui n'a pas de RIB sur sa fiche enseignant permanente.
     * Retourne [parId, parNomPrenom] (2 index, même repli id > nom+prénom
     * que le "Voir état" côté front — voir etatsDuMembre() en JS). Un état
     * plus récent écrase un plus ancien pour le même membre (parcours par
     * date de création croissante).
     *
     * $type : ne considère que les états de paie du même type d'indemnité
     * (demande utilisatrice : "la generation doit dependre du donner du
     * type d'indemnite") — un RIB saisi sous "indemnite_correction" ne doit
     * pas être réutilisé lors d'une génération "frais_deplacement", même
     * pour le même enseignant.
     */
    private function ribDejaSaisi(?string $type): array
    {
        $parId = [];
        $parNomPrenom = [];

        Etat_paie_indemnites::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->oldest('created_at')
            ->get(['details'])
            ->each(function (Etat_paie_indemnites $etat) use (&$parId, &$parNomPrenom) {
                foreach ($etat->details ?? [] as $detail) {
                    $rib = array_intersect_key($detail, array_flip([
                        'code_banque', 'code_guichet', 'numero_compte_bancaire', 'cle_rib',
                    ]));

                    if (empty(array_filter($rib))) {
                        continue;
                    }

                    if (! empty($detail['id'])) {
                        $parId[(string) $detail['id']] = $rib;
                    }

                    $cle = Str::lower(trim(($detail['nom'] ?? '').'|'.($detail['prenom'] ?? '')));

                    if ($cle !== '|') {
                        $parNomPrenom[$cle] = $rib;
                    }
                }
            });

        return [$parId, $parNomPrenom];
    }
}