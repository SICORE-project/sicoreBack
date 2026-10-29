<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Controllers\Api\Indemnites\Concerns\RefuseDepotAvantFinExamen;
use App\Http\Controllers\Controller;
use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\piece_justificatives;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Espace de l'enseignant : il dépose et consulte uniquement SES pièces justificatives
 * (l'enseignant est celui rattaché au compte connecté, jamais un identifiant fourni par le client).
 */
class MesPiecesController extends Controller
{
    use ApiResponseTrait;
    use RefuseDepotAvantFinExamen;

    private const TYPES_DEPOSABLES = ['service_fait', 'ordre_mission', 'rapport_mission', 'bulletin_salaire', 'accuse_reception'];

    public function index(Request $request)
    {
        $enseignantId = $this->enseignantId($request);

        if (! $enseignantId) {
            return $this->error('Aucun enseignant n\'est rattaché à votre compte.', 403);
        }

        $convocations = Convocations::query()
            ->where(function ($q) use ($enseignantId) {
                $q->whereHas('enseignants', fn ($e) => $e->where('enseignants.id', $enseignantId))
                    ->orWhereHas('centres', fn ($c) => $c->where('chef_centre_id', $enseignantId)->orWhere('president_jury_id', $enseignantId));
            })
            ->with('centres')
            ->orderByDesc('date_debut')
            ->get();

        $pieces = piece_justificatives::where('enseignant_id', $enseignantId)
            ->whereIn('convocation_id', $convocations->pluck('id'))
            ->get()
            ->groupBy('convocation_id');

        $donnees = $convocations->map(function (Convocations $convocation) use ($pieces, $enseignantId) {
            $parType = ($pieces[$convocation->id] ?? collect())->keyBy('type');
            $centres = $convocation->centres
                ->filter(fn ($centre) => $this->estDansCentre($convocation, $centre, $enseignantId))
                ->pluck('centre')
                ->values();

            return [
                'id' => $convocation->id,
                'objet' => $convocation->objet,
                'session' => $convocation->session,
                'date_debut' => $convocation->date_debut?->toDateString(),
                'date_fin' => $convocation->date_fin?->toDateString(),
                'centres' => $centres,
                'depot_ouvert' => $this->depotOuvert($convocation),
                'pieces' => collect(piece_justificatives::TYPES)->map(function ($libelle, $type) use ($parType) {
                    $piece = $parType->get($type);

                    return [
                        'type' => $type,
                        'libelle' => $libelle,
                        'deposable' => in_array($type, self::TYPES_DEPOSABLES, true),
                        'id' => $piece?->id,
                        'statut' => $piece?->statut,
                        'nom_original' => $piece?->nom_original,
                        'date_depot' => $piece?->date_depot?->toDateString(),
                        'commentaire_rejet' => $piece?->commentaire_rejet,
                    ];
                })->values(),
            ];
        })->values();

        return $this->success('Mes pièces justificatives.', $donnees);
    }

    public function deposer(Request $request)
    {
        $enseignantId = $this->enseignantId($request);

        if (! $enseignantId) {
            return $this->error('Aucun enseignant n\'est rattaché à votre compte.', 403);
        }

        $data = $request->validate([
            'convocation_id' => ['required', 'integer', 'exists:convocations,id'],
            'type' => ['required', 'in:' . implode(',', self::TYPES_DEPOSABLES)],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:1024'],
        ], [
            'document.max' => 'Le fichier ne doit pas dépasser 1 Mo.',
            'document.mimes' => 'Formats acceptés : PDF, JPG, PNG.',
        ]);

        $convocation = Convocations::with('centres')->find($data['convocation_id']);

        if (! $this->participe($convocation, $enseignantId)) {
            return $this->error('Vous ne participez pas à cette convocation.', 403);
        }

        if ($refus = $this->refusDepot($convocation->id)) {
            return $refus;
        }

        $existante = piece_justificatives::where([
            'convocation_id' => $convocation->id,
            'enseignant_id' => $enseignantId,
            'type' => $data['type'],
        ])->first();

        if ($existante && $existante->statut === 'valide') {
            return $this->error('Cette pièce est déjà validée : elle ne peut plus être remplacée.', 422);
        }

        $fichier = $request->file('document');
        $attributs = [
            'chemin' => $fichier->store('pieces-justificatives', 'public'),
            'nom_original' => $fichier->getClientOriginalName(),
            'mime_type' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'statut' => 'depose',
            'date_depot' => now()->toDateString(),
            'depositaire_id' => $request->user()->id,
            'commentaire_rejet' => null,
        ];

        if ($existante) {
            if ($existante->chemin && Storage::disk('public')->exists($existante->chemin)) {
                Storage::disk('public')->delete($existante->chemin);
            }
            $existante->update($attributs);
            $piece = $existante;
        } else {
            $piece = piece_justificatives::create($attributs + [
                'type' => $data['type'],
                'convocation_id' => $convocation->id,
                'enseignant_id' => $enseignantId,
                'centre_id' => $this->centreDe($convocation, $enseignantId),
            ]);
        }

        return $this->success('Pièce déposée avec succès.', $piece, 201);
    }

    public function download(Request $request, string $id)
    {
        $piece = piece_justificatives::where('enseignant_id', $this->enseignantId($request) ?? 0)->find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        if (! $piece->chemin || ! Storage::disk('public')->exists($piece->chemin)) {
            return $this->error('Fichier introuvable sur le serveur.', 404);
        }

        return Storage::disk('public')->response($piece->chemin, $piece->nom_original);
    }

    private function enseignantId(Request $request): ?int
    {
        return $request->user()?->enseignant_id ? (int) $request->user()->enseignant_id : null;
    }

    private function depotOuvert(Convocations $convocation): bool
    {
        if (! config('indemnites.depot_apres_examen', true) || ! $convocation->date_fin) {
            return true;
        }

        return now()->startOfDay()->gt($convocation->date_fin->copy()->startOfDay());
    }

    private function estDansCentre(Convocations $convocation, $centre, int $enseignantId): bool
    {
        if ((int) $centre->chef_centre_id === $enseignantId || (int) $centre->president_jury_id === $enseignantId) {
            return true;
        }

        return $convocation->enseignants()
            ->where('enseignants.id', $enseignantId)
            ->wherePivot('centre_id', $centre->id)
            ->exists();
    }

    private function participe(Convocations $convocation, int $enseignantId): bool
    {
        return $convocation->enseignants()->where('enseignants.id', $enseignantId)->exists()
            || $convocation->centres->contains(fn ($c) => (int) $c->chef_centre_id === $enseignantId || (int) $c->president_jury_id === $enseignantId);
    }

    private function centreDe(Convocations $convocation, int $enseignantId): ?int
    {
        foreach ($convocation->centres as $centre) {
            if ($this->estDansCentre($convocation, $centre, $enseignantId)) {
                return $centre->id;
            }
        }

        return null;
    }
}
