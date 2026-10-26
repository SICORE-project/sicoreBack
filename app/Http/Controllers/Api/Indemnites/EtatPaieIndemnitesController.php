<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Indemnites\StoreEtatPaieIndemniteRequest;
use App\Models\etat_paie_indemnites;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EtatPaieIndemnitesController extends Controller
{
    use ApiResponseTrait;

    private const TYPES = [
        'frais_deplacement' => 'Frais de déplacement',
        'indemnite_correction' => 'Indemnité de correction',
        'indemnite_surveillance' => 'Indemnité de surveillance',
    ];

    /** Liste des états de paie, filtrable par type, session, centre (lieu d'examen) et objet. */
    public function index(Request $request)
    {
        $etats = etat_paie_indemnites::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('session'), fn ($q) => $q->where('session', $request->query('session')))
            ->when($request->filled('centre'), fn ($q) => $q->where('lieu_examen', $request->query('centre')))
            ->when($request->filled('objet'), fn ($q) => $q->where('perimetre->objet', $request->query('objet')))
            ->latest('id')
            ->get();

        return $this->success('Liste des états de paie.', $etats);
    }

    public function store(StoreEtatPaieIndemniteRequest $request)
    {
        $donnees = $request->validated();
        $details = $donnees['details'] ?? [];

        $etat = etat_paie_indemnites::create(array_merge($donnees, [
            'reference' => $this->nouvelleReference(),
            'utilisateur_id' => $request->user()?->id,
            'date_generation' => now()->toDateString(),
            'details' => $details,
            'total_montant' => collect($details)->sum(fn ($ligne) => (float) ($ligne['montant'] ?? 0)),
            'statut' => 'brouillon',
        ]));

        return $this->success('État de paie créé avec succès.', $etat, 201);
    }

    public function show(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        return $etat
            ? $this->success('État de paie.', $etat)
            : $this->error('État de paie introuvable.', 404);
    }

    public function destroy(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->verrouille) {
            return $this->error('Cet état de paie est verrouillé.', 423);
        }

        if ($etat->statut !== 'brouillon') {
            return $this->error('Seul un état de paie en brouillon peut être supprimé.', 422);
        }

        $etat->delete();

        return $this->success('État de paie supprimé avec succès.');
    }

    /** Brouillon → validé par la DAGE : l'état est alors verrouillé (plus de suppression). */
    public function valider(Request $request, string $id)
    {
        return $this->changerStatut($id, 'brouillon', 'valide', "Seul un état en brouillon peut être validé.", [
            'verrouille' => true,
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ], 'État de paie validé.');
    }

    /** Validé → transmis (au service de paie). */
    public function transmettre(string $id)
    {
        return $this->changerStatut($id, 'valide', 'transmis', "Seul un état validé peut être transmis.", [
            'transmit_sica' => true,
        ], 'État de paie transmis.');
    }

    /** Transmis → archivé. */
    public function archiver(Request $request, string $id)
    {
        return $this->changerStatut($id, 'transmis', 'archive', "Seul un état transmis peut être archivé.", [
            'archive_par' => $request->user()?->id,
            'archive_at' => now(),
        ], 'État de paie archivé.');
    }

    private function changerStatut(string $id, string $statutRequis, string $nouveau, string $refus, array $champs, string $message)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->statut !== $statutRequis) {
            return $this->error($refus, 422);
        }

        $etat->update($champs + ['statut' => $nouveau]);

        return $this->success($message, $etat);
    }

    /** Fiche PDF d'un tableau de membres (sans rien enregistrer). */
    public function fichePdf(Request $request)
    {
        $donnees = $request->validate([
            'type_libelle' => ['required', 'string', 'max:100'],
            'objet' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:150'],
            'centre' => ['nullable', 'string', 'max:255'],
            'details' => ['required', 'array', 'min:1'],
        ]);

        $pdf = Pdf::loadView('pdf.etat-paie-fiche', [
            'typeLibelle' => $donnees['type_libelle'],
            'objet' => $donnees['objet'] ?? null,
            'session' => $donnees['session'] ?? null,
            'centre' => $donnees['centre'] ?? null,
            'details' => $donnees['details'],
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etat-de-paiement.pdf"',
        ]);
    }

    /** PDF d'un état de paie enregistré, tel qu'il a été figé à sa création. */
    public function pdf(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        $pdf = Pdf::loadView('pdf.etat-paie-fiche', [
            'typeLibelle' => self::TYPES[$etat->type] ?? $etat->type,
            'objet' => $etat->perimetre['objet'] ?? null,
            'session' => $etat->session,
            'centre' => $etat->lieu_examen,
            'details' => $etat->details ?? [],
            'reference' => $etat->reference,
            'statut' => $etat->statut,
            'periode' => ($etat->periode_debut && $etat->periode_fin)
                ? $etat->periode_debut->format('d/m/Y') . ' au ' . $etat->periode_fin->format('d/m/Y')
                : null,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etat-de-paie-' . $etat->reference . '.pdf"',
        ]);
    }

    private function nouvelleReference(): string
    {
        do {
            $reference = 'EP-' . strtoupper(Str::random(8));
        } while (etat_paie_indemnites::where('reference', $reference)->exists());

        return $reference;
    }
}
