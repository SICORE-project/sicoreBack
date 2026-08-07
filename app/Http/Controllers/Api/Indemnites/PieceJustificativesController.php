<?php

namespace App\Http\Controllers;

use App\Models\piece_justificatives;
use App\Services\AccuseReceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PieceJustificativesController extends Controller
{
    public function index(Request $request)
    {
        $query = piece_justificatives::with(['convocation', 'depositaire', 'verificateur', 'validateur']);

        if (! $this->isAdmin($request)) {
            $query->where('depositaire_id', $request->user()->id);
        }

        foreach (['statut', 'type', 'convocation_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate($this->perPage($request)),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'convocation_id' => ['required', 'integer', 'exists:convocations,id'],
            'type' => ['required', 'string', 'max:100'],
            'session' => ['nullable', 'string', 'max:100'],
            'fichiers' => ['required', 'array', 'min:1', 'max:10'],
            'fichiers.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $paths = [];
        try {
            $pieces = DB::transaction(function () use ($validated, $request, &$paths) {
                return collect($validated['fichiers'])->map(function ($file) use ($validated, $request, &$paths) {
                    $path = $file->store("pieces-justificatives/{$validated['convocation_id']}", 'local');
                    $paths[] = $path;

                    $piece = piece_justificatives::create([
                        'type' => $validated['type'], 'document_url' => $path, 'chemin' => $path,
                        'nom_original' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(),
                        'taille' => $file->getSize(), 'date_depot' => today(),
                        'convocation_id' => $validated['convocation_id'], 'depositaire_id' => $request->user()->id,
                    ]);
                    app(AccuseReceptionService::class)->genererPourPiece($piece->load('depositaire'), $validated['session'] ?? null);
                    return $piece;
                });
            });
        } catch (\Throwable $exception) {
            foreach ($paths as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pièces justificatives déposées avec succès.',
            'data' => $pieces,
        ], 201);
    }

    public function show(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAccess($request, $piece);

        return response()->json([
            'success' => true,
            'data' => $piece->load(['convocation', 'depositaire', 'verificateur', 'validateur']),
        ]);
    }

    public function download(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAccess($request, $piece);

        $path = $piece->chemin ?? $piece->document_url;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Fichier introuvable.');

        return Storage::disk('local')->download($path, $piece->nom_original ?? basename($path));
    }

    public function classer(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAccess($request, $piece);
        abort_if($piece->statut === 'valide', 422, 'Une pièce validée ne peut plus être reclassée.');

        $validated = $request->validate(['type' => ['required', 'string', 'max:100']]);
        $piece->update($validated);

        return response()->json(['success' => true, 'data' => $piece->fresh()]);
    }

    public function verifier(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'conforme' => ['required', 'boolean'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        $piece->update([
            'conforme' => $validated['conforme'],
            'commentaire_verification' => $validated['commentaire'] ?? null,
            'verifie_par' => $request->user()->id,
            'verifie_at' => now(),
        ]);
        app(AccuseReceptionService::class)->synchroniserDossier($piece->fresh());

        return response()->json(['success' => true, 'data' => $piece->fresh()]);
    }

    public function valider(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAdmin($request);
        abort_if($piece->conforme !== true, 422, 'La pièce doit être déclarée conforme avant validation.');

        $piece->update([
            'statut' => 'valide',
            'valide_par' => $request->user()->id,
            'valide_at' => now(),
            'commentaire_rejet' => null,
        ]);

        return response()->json(['success' => true, 'data' => $piece->fresh()]);
    }

    public function rejeter(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['commentaire' => ['required', 'string', 'min:3', 'max:2000']]);

        $piece->update([
            'statut' => 'rejete',
            'valide_par' => $request->user()->id,
            'valide_at' => now(),
            'commentaire_rejet' => $validated['commentaire'],
        ]);

        return response()->json(['success' => true, 'data' => $piece->fresh()]);
    }

    public function notifier(Request $request, piece_justificatives $piece)
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($piece->statut, ['valide', 'rejete'], true), 422, 'La pièce doit être validée ou rejetée.');

        $message = $piece->statut === 'valide'
            ? 'Votre pièce justificative a été validée.'
            : 'Votre pièce justificative a été rejetée : '.$piece->commentaire_rejet;

        $piece->update(['notification_at' => now(), 'notification_message' => $message]);

        return response()->json(['success' => true, 'message' => $message, 'data' => $piece->fresh()]);
    }

    private function isAdmin(Request $request): bool
    {
        return strtolower((string) $request->user()->loadMissing('role')->role?->libelle) === 'administrateur';
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request), 403, 'Réservé aux administrateurs.');
    }

    private function authorizeAccess(Request $request, piece_justificatives $piece): void
    {
        abort_unless($this->isAdmin($request) || $piece->depositaire_id === $request->user()->id, 403, 'Accès non autorisé.');
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
