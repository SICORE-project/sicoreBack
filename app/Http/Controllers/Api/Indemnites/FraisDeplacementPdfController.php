<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\MissionDeplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class FraisDeplacementPdfController extends Controller
{
    use ApiResponseTrait;

   
    private const RELATIONS_POUR_PDF = [
        'beneficiaire',
        'convocation',
        'justificatifs',
    ];

    /**
     * Génère le PDF de la fiche et le stocke sur le disque `public`.
     *
     * NOTE: nécessite le package barryvdh/laravel-dompdf, ajouté à
     * composer.json (voir README-corrections.md).
     */
    public function generer(string $id)
    {
        $mission = MissionDeplacement::with(self::RELATIONS_POUR_PDF)->find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return $this->error(
                "La génération de PDF nécessite le package 'barryvdh/laravel-dompdf' (composer require barryvdh/laravel-dompdf).",
                501
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.frais-deplacement', [
            'mission' => $mission,
        ]);

        $chemin = "frais-deplacement/{$mission->id}/fiche-{$mission->id}.pdf";
        Storage::disk('public')->put($chemin, $pdf->output());

        return $this->success('PDF généré avec succès.', [
            'mission_id' => $mission->id,
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ]);
    }

    /**
     * Télécharge le PDF de la fiche — régénéré à CHAQUE téléchargement
     * (contrairement à ConvocationPdfController::download(), qui réutilise
     * le fichier déjà généré) : la fiche peut être modifiée après coup
     * (voir update()), un PDF mis en cache aurait pu rester périmé et
     * afficher d'anciennes données (bug constaté : "le PDF téléchargé
     * affiche toujours..." après un changement de la vue pdf).
     */
    public function download(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $resultat = $this->generer($id);

        if ($resultat->getData()->success !== true) {
            return $resultat;
        }

        $chemin = "frais-deplacement/{$mission->id}/fiche-{$mission->id}.pdf";

        return Storage::disk('public')->download($chemin, "fiche-deplacement-{$mission->id}.pdf");
    }

    /** Fiches non payables : pas encore calculees, ou rejetees. */
    private const STATUTS_EXCLUS_ETAT = ['brouillon', 'rejete'];

    /**
     * État de paiement (PDF) des frais de déplacement de TOUS les
     * bénéficiaires d'une convocation ayant une fiche de déplacement : une
     * ligne par bénéficiaire (matricule, nom, catégorie, fonction, trajet,
     * montant), regroupées par centre, total général et montant en lettres.
     *
     * Query : convocation_id (id ou slug, obligatoire), centre (libellé,
     * facultatif — limite l'état à ce centre), apercu (1 = affichage dans le
     * navigateur au lieu du téléchargement).
     */
    public function etatPaiement(Request $request)
    {
        $donnees = $this->donneesEtatPaiement($request);

        if (! is_array($donnees)) {
            return $donnees;
        }

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return $this->error(
                "La génération de PDF nécessite le package 'barryvdh/laravel-dompdf' (composer require barryvdh/laravel-dompdf).",
                501
            );
        }

        $convocation = $donnees['convocation'];
        $centreFiltre = $donnees['centreFiltre'];
        $total = $donnees['lignes']->sum('montant');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.etat-paiement-frais-deplacement', [
            'convocation' => $convocation,
            'groupes' => $donnees['groupes'],
            'total' => $total,
            'totalEnLettres' => $this->enLettres($total),
            'nombreBeneficiaires' => $donnees['lignes']->count(),
            'centreFiltre' => $centreFiltre,
            'centresLibelle' => $centreFiltre ?? ($convocation->centres->pluck('centre')->filter()->unique()->implode(', ') ?: '—'),
            'periode' => $this->periode($convocation->date_debut, $convocation->date_fin),
            'dateEdition' => now()->format('d/m/Y'),
        ])->setPaper('a4', 'landscape');

        $nomFichier = $this->nomFichierEtat($convocation, $centreFiltre).'.pdf';

        // apercu=1 : affiché dans le navigateur (Content-Disposition inline)
        // avant téléchargement ; sinon téléchargé directement.
        return $request->boolean('apercu') ? $pdf->stream($nomFichier) : $pdf->download($nomFichier);
    }

    /**
     * Fichier SICA (CSV) de l'état de paiement : mêmes bénéficiaires et
     * montants que le PDF, une ligne par bénéficiaire avec ses coordonnées
     * bancaires. Même format que le fichier SICA de l'état des salaires
     * (PayrollPageController::csvDownload()) : UTF-8 avec BOM, séparateur
     * ";", cellules neutralisées contre l'injection de formules.
     */
    public function etatPaiementSica(Request $request)
    {
        $donnees = $this->donneesEtatPaiement($request);

        if (! is_array($donnees)) {
            return $donnees;
        }

        $convocation = $donnees['convocation'];
        $colonnes = [
            'N°', 'Matricule', 'Prénom', 'Nom', 'Catégorie', 'Fonction', 'Centre',
            'Code banque', 'Code guichet', 'Numéro de compte', 'Clé RIB', 'RIB complet',
            'Montant (FCFA)', 'Objet', 'Session',
        ];

        $lignes = $donnees['lignes']->values()->map(fn (array $ligne, int $index) => [
            $index + 1,
            $ligne['matricule'],
            $ligne['prenom'],
            $ligne['nom'],
            $ligne['categorie'],
            $ligne['fonction'],
            $ligne['centre'],
            $ligne['rib']['code_banque'],
            $ligne['rib']['code_guichet'],
            $ligne['rib']['numero_compte'],
            $ligne['rib']['cle_rib'],
            $ligne['rib']['complet'] ? 'Oui' : 'Non',
            (int) round($ligne['montant']),
            $convocation->objet,
            $convocation->session,
        ]);

        $nomFichier = 'sica-'.$this->nomFichierEtat($convocation, $donnees['centreFiltre']).'.csv';

        return response()->streamDownload(function () use ($colonnes, $lignes): void {
            $flux = fopen('php://output', 'wb');
            fwrite($flux, "\xEF\xBB\xBF");
            fputcsv($flux, $colonnes, ';', '"', '');

            foreach ($lignes as $ligne) {
                fputcsv($flux, array_map(function (mixed $cellule): string {
                    $valeur = (string) ($cellule ?? '');

                    return preg_match('/^[=+@\-\t\r]/', $valeur) ? "'".$valeur : $valeur;
                }, $ligne), ';', '"', '');
            }

            fclose($flux);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Données communes au PDF et au fichier SICA : bénéficiaires de la
     * convocation ayant une fiche payable, avec rôle, montant et RIB.
     * Renvoie une réponse d'erreur si la convocation est introuvable.
     */
    private function donneesEtatPaiement(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $convocation = Convocations::trouverParSlugOuId((string) $request->query('convocation_id', ''));

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->load(['centres.chefCentre', 'centres.presidentJury', 'enseignants']);
        $centreFiltre = trim((string) $request->query('centre', '')) ?: null;

        // Rôle de chaque bénéficiaire dans la convocation (centre + fonction) :
        // chef de centre / président du jury d'un centre, ou membre (pivot).
        $roles = [];
        foreach ($convocation->centres as $centre) {
            if ($centre->chef_centre_id) {
                $roles[$centre->chef_centre_id] ??= ['centre' => $centre->centre, 'fonction' => 'Chef de centre', 'categorie' => $centre->chef_centre_categorie_personnel];
            }
            if ($centre->president_jury_id) {
                $roles[$centre->president_jury_id] ??= ['centre' => $centre->centre, 'fonction' => 'Président du jury', 'categorie' => $centre->president_jury_categorie_personnel];
            }
        }
        $centresParId = $convocation->centres->keyBy('id');
        foreach ($convocation->enseignants as $enseignant) {
            $centre = $centresParId->get($enseignant->pivot->centre_id ?? null)
                ?? ($convocation->centres->count() === 1 ? $convocation->centres->first() : null);
            $roles[$enseignant->id] ??= [
                'centre' => $centre?->centre,
                'fonction' => $enseignant->pivot->fonction ?: 'Membre du jury',
                'categorie' => $enseignant->pivot->categorie_personnel ?? null,
            ];
        }

        $missions = MissionDeplacement::with('beneficiaire.comptesBancaires')
            ->where('convocation_id', $convocation->id)
            ->whereNotIn('statut', self::STATUTS_EXCLUS_ETAT)
            ->get();

        $lignes = $missions->map(function (MissionDeplacement $mission) use ($roles) {
            $role = $roles[$mission->beneficiaire_id] ?? [];
            $beneficiaire = $mission->beneficiaire;
            $categorie = $role['categorie'] ?? $mission->statut_agent;

            return [
                'centre' => $role['centre'] ?? 'Sans centre',
                'matricule' => $beneficiaire?->matricule,
                'prenom' => $beneficiaire?->prenom,
                'nom' => $beneficiaire?->nom,
                'nom_complet' => trim(($beneficiaire?->prenom ?? '').' '.($beneficiaire?->nom ?? '')) ?: '—',
                'categorie' => $categorie ? ucfirst((string) $categorie) : '—',
                'fonction' => $role['fonction'] ?? '—',
                'lieu_depart' => $mission->lieu_depart,
                'lieu_destination' => $mission->lieu_destination,
                'periode' => $this->periode($mission->date_depart, $mission->date_retour),
                'rib' => $this->rib($beneficiaire),
                // Montant approuvé s'il existe, sinon montant calculé.
                'montant' => (float) ($mission->montant_approuve ?? $mission->montant_calcule ?? 0),
            ];
        })
            ->when($centreFiltre, fn ($lignes) => $lignes->filter(
                fn ($ligne) => mb_strtolower((string) $ligne['centre']) === mb_strtolower($centreFiltre)
            ))
            ->sortBy([['centre', 'asc'], ['nom_complet', 'asc']])
            ->values();

        $groupes = $lignes->groupBy('centre')->map(fn ($lignesCentre, $centre) => [
            'centre' => $centre,
            'lignes' => $lignesCentre->values()->all(),
            'total' => $lignesCentre->sum('montant'),
        ])->values()->all();

        return [
            'convocation' => $convocation,
            'centreFiltre' => $centreFiltre,
            'lignes' => $lignes,
            'groupes' => $groupes,
        ];
    }

    /**
     * Coordonnées bancaires du bénéficiaire, prises dans les informations de
     * l'enseignant (demande utilisatrice) : exactement le "Compte bancaire"
     * affiché sur sa fiche — compte principal, à défaut son premier compte
     * (même règle que Personnel\EnseignantResource). "affichage" =
     * "34556 23230 345678909876 26" (banque 5, guichet 5, compte 12, clé 2).
     */
    private function rib($enseignant): array
    {
        $comptes = $enseignant?->comptesBancaires ?? collect();
        $compte = $comptes->firstWhere('est_principal', true) ?? $comptes->first();

        $rib = [
            'code_banque' => (string) ($compte?->code_banque ?? ''),
            'code_guichet' => (string) ($compte?->code_guichet ?? ''),
            'numero_compte' => (string) ($compte?->numero_compte ?? ''),
            'cle_rib' => (string) ($compte?->cle_rib ?? ''),
        ];

        $rib['complet'] = strlen($rib['code_banque']) === 5
            && strlen($rib['code_guichet']) === 5
            && strlen($rib['numero_compte']) === 12
            && strlen($rib['cle_rib']) === 2;

        $rib['affichage'] = trim(implode(' ', array_filter([
            $rib['code_banque'], $rib['code_guichet'], $rib['numero_compte'], $rib['cle_rib'],
        ], fn ($partie) => $partie !== '')));

        return $rib;
    }

    private function nomFichierEtat($convocation, ?string $centre): string
    {
        return 'etat-paiement-frais-deplacement-'.Str::slug($convocation->objet ?: 'convocation')
            .($centre ? '-'.Str::slug($centre) : '');
    }

    private function periode($debut, $fin): string
    {
        $format = fn ($date) => $date ? Carbon::parse($date)->format('d/m/Y') : null;
        $debut = $format($debut);
        $fin = $format($fin);

        if ($debut && $fin) {
            return $debut === $fin ? $debut : "du {$debut} au {$fin}";
        }

        return $debut ?? $fin ?? '—';
    }

    /** Montant en toutes lettres (extension intl) ; null si indisponible. */
    private function enLettres(float $montant): ?string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return null;
        }

        $texte = (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format((int) round($montant));

        return $texte === false ? null : $texte;
    }
}
