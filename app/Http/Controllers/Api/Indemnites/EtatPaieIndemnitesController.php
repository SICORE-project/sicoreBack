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

        $etat->delete();

        return $this->success('État de paie supprimé avec succès.');
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

    private function nouvelleReference(): string
    {
        do {
            $reference = 'EP-' . strtoupper(Str::random(8));
        } while (etat_paie_indemnites::where('reference', $reference)->exists());

        return $reference;
    }
}
