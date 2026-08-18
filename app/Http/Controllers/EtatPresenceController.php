<?php

namespace App\Http\Controllers;

use App\Models\EtatPresence;
use App\Models\Absence;
use App\Models\Retard;
use Illuminate\Http\Request;

class EtatPresenceController extends Controller
{
    // --- CRUD de base ---

    public function index(Request $request)
    {
        $query = EtatPresence::with(['enseignant', 'ia', 'ief', 'validePar', 'transmisPar']);

        if ($request->filled('mois')) {
            $query->where('mois', $request->mois);
        }
        if ($request->filled('annee')) {
            $query->where('annee', $request->annee);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('ia_id')) {
            $query->where('ia_id', $request->ia_id);
        }
        if ($request->filled('ief_id')) {
            $query->where('ief_id', $request->ief_id);
        }
        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        $etats = $query->orderBy('annee', 'desc')->orderBy('mois', 'desc')->get();

        return response()->json($etats);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020',
            'enseignant_id' => 'required|exists:enseignants,id',
            'nombre_jour' => 'required|integer|min:0|max:31',
            'nombre_jours_travailles' => 'nullable|integer|min:0|max:31',
            'ia_id' => 'nullable|exists:ias,id',
            'ief_id' => 'nullable|exists:iefs,id',
            'observation' => 'nullable|string',
        ]);

        $existing = EtatPresence::where('enseignant_id', $validated['enseignant_id'])
            ->where('mois', $validated['mois'])
            ->where('annee', $validated['annee'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Un état de présence existe déjà pour cet enseignant ce mois.'
            ], 422);
        }

        $etat = EtatPresence::create(array_merge($validated, [
            'nombre_jours_travailles' => $validated['nombre_jours_travailles'] ?? $validated['nombre_jour'],
            'statut' => 'brouillon',
        ]));

        return response()->json([
            'message' => 'État de présence créé avec succès.',
            'data' => $etat->load(['enseignant', 'ia', 'ief'])
        ], 201);
    }

    public function show(string $id)
    {
        $etat = EtatPresence::with(['enseignant', 'ia', 'ief', 'absences', 'retards', 'validePar', 'transmisPar'])
            ->findOrFail($id);

        return response()->json($etat);
    }

    public function update(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut === 'transmis') {
            return response()->json([
                'message' => 'Impossible de modifier un état de présence déjà transmis.'
            ], 403);
        }

        $validated = $request->validate([
            'nombre_jour' => 'nullable|integer|min:0|max:31',
            'nombre_jours_travailles' => 'nullable|integer|min:0|max:31',
            'ia_id' => 'nullable|exists:ias,id',
            'ief_id' => 'nullable|exists:iefs,id',
            'observation' => 'nullable|string',
        ]);

        $etat->update($validated);

        return response()->json([
            'message' => 'État de présence modifié avec succès.',
            'data' => $etat->load(['enseignant', 'ia', 'ief'])
        ]);
    }

    public function destroy(string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut !== 'brouillon') {
            return response()->json([
                'message' => 'Seul un état en brouillon peut être supprimé.'
            ], 403);
        }

        $etat->delete();

        return response()->json([
            'message' => 'État de présence supprimé avec succès.'
        ]);
    }

    // --- Sous-tâche : Enregistrer une absence ---

    public function enregistrerAbsence(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut === 'transmis') {
            return response()->json([
                'message' => 'Impossible d\'ajouter une absence à un état transmis.'
            ], 403);
        }

        $validated = $request->validate([
            'date_absence' => 'required|date',
            'motif' => 'nullable|string',
            'justifiee' => 'nullable|boolean',
            'piece_justificative' => 'nullable|string',
        ]);

        $absence = Absence::create([
            'etat_presence_id' => $etat->id,
            'enseignant_id' => $etat->enseignant_id,
            'date_absence' => $validated['date_absence'],
            'motif' => $validated['motif'] ?? null,
            'justifiee' => $validated['justifiee'] ?? false,
            'piece_justificative' => $validated['piece_justificative'] ?? null,
            'date_justification' => ($validated['justifiee'] ?? false) ? now()->toDateString() : null,
            'retenue_montant' => 0,
        ]);

        $etat->recalculerRetenues();

        return response()->json([
            'message' => 'Absence enregistrée avec succès.',
            'data' => $absence,
            'etat' => $etat->fresh()->load(['absences', 'retards'])
        ], 201);
    }

    // --- Sous-tâche : Enregistrer un retard ---

    public function enregistrerRetard(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut === 'transmis') {
            return response()->json([
                'message' => 'Impossible d\'ajouter un retard à un état transmis.'
            ], 403);
        }

        $validated = $request->validate([
            'date_retard' => 'required|date',
            'duree_minutes' => 'required|integer|min:1',
            'motif' => 'nullable|string',
            'justifie' => 'nullable|boolean',
        ]);

        $retard = Retard::create([
            'etat_presence_id' => $etat->id,
            'enseignant_id' => $etat->enseignant_id,
            'date_retard' => $validated['date_retard'],
            'duree_minutes' => $validated['duree_minutes'],
            'motif' => $validated['motif'] ?? null,
            'justifie' => $validated['justifie'] ?? false,
            'retenue_montant' => 0,
        ]);

        $etat->recalculerRetenues();

        return response()->json([
            'message' => 'Retard enregistré avec succès.',
            'data' => $retard,
            'etat' => $etat->fresh()->load(['absences', 'retards'])
        ], 201);
    }

    // --- Sous-tâche : Justifier ou non une absence ---

    public function justifierAbsence(Request $request, string $id, string $absenceId)
    {
        $etat = EtatPresence::findOrFail($id);
        $absence = Absence::where('etat_presence_id', $etat->id)->findOrFail($absenceId);

        $validated = $request->validate([
            'justifiee' => 'required|boolean',
            'piece_justificative' => 'nullable|string',
            'motif' => 'nullable|string',
        ]);

        $absence->update([
            'justifiee' => $validated['justifiee'],
            'piece_justificative' => $validated['piece_justificative'] ?? $absence->piece_justificative,
            'motif' => $validated['motif'] ?? $absence->motif,
            'date_justification' => $validated['justifiee'] ? now()->toDateString() : null,
        ]);

        return response()->json([
            'message' => $validated['justifiee']
                ? 'Absence justifiée avec succès.'
                : 'Absence marquée comme non justifiée.',
            'data' => $absence
        ]);
    }

    // --- Sous-tâche : Calculer les retenues liées aux absences ---

    public function calculerRetenuesAbsences(Request $request, string $id)
    {
        $etat = EtatPresence::with('absences')->findOrFail($id);

        $validated = $request->validate([
            'montant_journalier' => 'required|numeric|min:0',
        ]);

        $montantJournalier = $validated['montant_journalier'];

        foreach ($etat->absences as $absence) {
            $retenue = $absence->justifiee ? 0 : $montantJournalier;
            $absence->update(['retenue_montant' => $retenue]);
        }

        $etat->recalculerRetenues();

        return response()->json([
            'message' => 'Retenues absences calculées avec succès.',
            'retenue_absences' => $etat->fresh()->retenue_absences,
            'detail' => $etat->absences
        ]);
    }

    // --- Sous-tâche : Calculer les retenues liées aux retards ---

    public function calculerRetenuesRetards(Request $request, string $id)
    {
        $etat = EtatPresence::with('retards')->findOrFail($id);

        $validated = $request->validate([
            'montant_par_minute' => 'required|numeric|min:0',
        ]);

        $montantParMinute = $validated['montant_par_minute'];

        foreach ($etat->retards as $retard) {
            $retenue = $retard->justifie ? 0 : ($retard->duree_minutes * $montantParMinute);
            $retard->update(['retenue_montant' => $retenue]);
        }

        $etat->recalculerRetenues();

        return response()->json([
            'message' => 'Retenues retards calculées avec succès.',
            'retenue_retards' => $etat->fresh()->retenue_retards,
            'detail' => $etat->retards
        ]);
    }

    // --- Sous-tâche : Valider l'état de présence du mois ---

    public function valider(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut !== 'brouillon' && $etat->statut !== 'rejete') {
            return response()->json([
                'message' => 'Seul un état en brouillon ou rejeté peut être validé.'
            ], 403);
        }

        $etat->update([
            'statut' => 'valide',
            'date_validation' => now()->toDateString(),
            'valide_par' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'État de présence validé avec succès.',
            'data' => $etat->load(['enseignant', 'ia', 'ief', 'validePar'])
        ]);
    }

    // --- Sous-tâche : Rejeter un état de présence ---

    public function rejeter(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut !== 'valide') {
            return response()->json([
                'message' => 'Seul un état validé peut être rejeté.'
            ], 403);
        }

        $validated = $request->validate([
            'observation' => 'required|string',
        ]);

        $etat->update([
            'statut' => 'rejete',
            'observation' => $validated['observation'],
        ]);

        return response()->json([
            'message' => 'État de présence rejeté.',
            'data' => $etat
        ]);
    }

    // --- Sous-tâche : Transmettre l'état de présence au traitement de la paie ---

    public function transmettre(Request $request, string $id)
    {
        $etat = EtatPresence::findOrFail($id);

        if ($etat->statut !== 'valide') {
            return response()->json([
                'message' => 'Seul un état validé peut être transmis à la paie.'
            ], 403);
        }

        $etat->update([
            'statut' => 'transmis',
            'date_transmission' => now()->toDateString(),
            'transmis_par' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'État de présence transmis au traitement de la paie avec succès.',
            'data' => $etat->load(['enseignant', 'ia', 'ief', 'transmisPar'])
        ]);
    }

    // --- Statistiques du mois ---

    public function statistiques(Request $request)
    {
        $mois = $request->input('mois', now()->month);
        $annee = $request->input('annee', now()->year);

        $etats = EtatPresence::where('mois', $mois)->where('annee', $annee);

        $totalEnseignants = $etats->count();
        $totalAbsences = $etats->sum('nombre_absences');
        $totalRetards = $etats->sum('nombre_retards');
        $totalRetenues = $etats->sum('total_retenues');

        $parStatut = EtatPresence::where('mois', $mois)->where('annee', $annee)
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return response()->json([
            'mois' => $mois,
            'annee' => $annee,
            'total_enseignants' => $totalEnseignants,
            'total_absences' => $totalAbsences,
            'total_retards' => $totalRetards,
            'total_retenues' => $totalRetenues,
            'par_statut' => $parStatut,
        ]);
    }

    // --- Lister les absences d'un état ---

    public function listeAbsences(string $id)
    {
        $etat = EtatPresence::findOrFail($id);
        $absences = $etat->absences()->with('enseignant')->get();

        return response()->json($absences);
    }

    // --- Lister les retards d'un état ---

    public function listeRetards(string $id)
    {
        $etat = EtatPresence::findOrFail($id);
        $retards = $etat->retards()->with('enseignant')->get();

        return response()->json($retards);
    }
}
