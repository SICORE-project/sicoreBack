<?php

namespace App\Http\Controllers;

use App\Models\bultins;
use App\Models\detail_bultins;
use App\Models\enseignants;
use App\Models\EtatPresence;
use App\Models\rubrique_bultins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulletinSalaireController extends Controller
{
    const VALEUR_POINT = 291.67;
    const TAUX_IPRES = 0.056;
    const TAUX_IR = 0.05;
    const TAUX_CSS = 0.01;

    const INDEMNITES_PAR_CORPS = [
        'Professeur d\'Enseignement Secondaire' => ['logement' => 80000, 'transport' => 30000],
        'Professeur d\'Enseignement Moyen'      => ['logement' => 60000, 'transport' => 25000],
        'Instituteur'                            => ['logement' => 50000, 'transport' => 20000],
        'Maître d\'Internat'                     => ['logement' => 40000, 'transport' => 15000],
    ];
    const INDEMNITES_DEFAUT = ['logement' => 50000, 'transport' => 20000];

    public function index(Request $request)
    {
        $query = bultins::with(['enseignant.user', 'enseignant.corpsEnseignant', 'ia', 'details.rubrique'])
            ->orderByDesc('created_at');

        if ($request->statut) {
            $query->where('statut', $request->statut);
        }
        if ($request->mois && $request->annee) {
            $date = $request->annee . '-' . str_pad($request->mois, 2, '0', STR_PAD_LEFT) . '-01';
            $query->where('mois_validite', $date);
        }
        if ($request->enseignant_id) {
            $query->where('enseignant_id', $request->enseignant_id);
        }
        if ($request->ia_id) {
            $query->where('ia_id', $request->ia_id);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('enseignant.user', function ($q2) use ($search) {
                      $q2->where('nom', 'like', "%{$search}%")
                         ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }

        $bulletins = $query->paginate($request->per_page ?? 10);

        $items = $bulletins->getCollection()->map(fn ($b) => $this->formatBulletin($b));

        return response()->json([
            'data' => $items,
            'pagination' => [
                'current_page' => $bulletins->currentPage(),
                'last_page' => $bulletins->lastPage(),
                'per_page' => $bulletins->perPage(),
                'total' => $bulletins->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $bulletin = bultins::with(['enseignant.user', 'enseignant.corpsEnseignant', 'ia', 'details.rubrique'])
            ->findOrFail($id);

        return response()->json($this->formatBulletinDetail($bulletin));
    }

    public function apercu(Request $request)
    {
        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020',
        ]);

        $enseignant = enseignants::with(['user', 'corpsEnseignant'])->findOrFail($request->enseignant_id);

        if (!$enseignant->user) {
            return response()->json(['message' => 'Cet enseignant n\'a pas de compte utilisateur associé.'], 422);
        }

        $calcul = $this->calculerSalaire($enseignant, $request->mois, $request->annee);

        return response()->json([
            'enseignant' => [
                'id' => $enseignant->id,
                'matricule' => $enseignant->user->id . '-' . str_pad($enseignant->id, 4, '0', STR_PAD_LEFT),
                'nom' => $enseignant->user->nom,
                'prenom' => $enseignant->user->prenom,
                'indice' => $enseignant->indice,
                'corps' => $enseignant->corpsEnseignant?->libelle ?? '-',
            ],
            'periode' => [
                'mois' => $request->mois,
                'annee' => $request->annee,
            ],
            'calcul' => $calcul,
        ]);
    }

    public function generer(Request $request)
    {
        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020',
            'ia_id' => 'nullable|exists:ias,id',
        ]);

        $enseignant = enseignants::with(['user', 'corpsEnseignant'])->findOrFail($request->enseignant_id);

        if (!$enseignant->user) {
            return response()->json(['message' => 'Cet enseignant n\'a pas de compte utilisateur associé.'], 422);
        }

        $moisValidite = sprintf('%d-%02d-01', $request->annee, $request->mois);

        $existe = bultins::where('enseignant_id', $request->enseignant_id)
            ->where('mois_validite', $moisValidite)
            ->whereIn('statut', ['genere', 'valide', 'paye'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Un bulletin existe déjà pour cet agent sur cette période.',
            ], 409);
        }

        $calcul = $this->calculerSalaire($enseignant, $request->mois, $request->annee);

        $bulletin = DB::transaction(function () use ($enseignant, $moisValidite, $calcul, $request) {
            $reference = $this->genererReference($request->annee, $request->mois);
            $matricule = $enseignant->user->id . '-' . str_pad($enseignant->id, 4, '0', STR_PAD_LEFT);

            $bulletin = bultins::create([
                'matricule' => $matricule,
                'mois_validite' => $moisValidite,
                'date_enregistrement' => now()->toDateString(),
                'numero_ordre' => $reference,
                'reference' => $reference,
                'statut' => 'genere',
                'salaire_brut' => $calcul['brut'],
                'total_retenues' => $calcul['total_retenues'],
                'net_a_payer' => $calcul['net'],
                'enseignant_id' => $enseignant->id,
                'ia_id' => $request->ia_id,
            ]);

            foreach ($calcul['lignes'] as $ligne) {
                $rubrique = rubrique_bultins::firstOrCreate(
                    ['libelle' => $ligne['rubrique']],
                    ['type' => $ligne['type']]
                );

                detail_bultins::create([
                    'montant_gains' => $ligne['type'] === 'gain' ? $ligne['montant'] : 0,
                    'montant_retenus' => $ligne['type'] === 'retenue' ? $ligne['montant'] : 0,
                    'bultin_id' => $bulletin->id,
                    'rubrique_bultin_id' => $rubrique->id,
                ]);
            }

            return $bulletin;
        });

        $bulletin->load(['enseignant.user', 'enseignant.corpsEnseignant', 'ia', 'details.rubrique']);

        return response()->json([
            'message' => 'Bulletin généré avec succès.',
            'bulletin' => $this->formatBulletinDetail($bulletin),
        ], 201);
    }

    public function genererEnMasse(Request $request)
    {
        $request->validate([
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020',
            'ia_id' => 'nullable|exists:ias,id',
            'enseignant_ids' => 'nullable|array',
            'enseignant_ids.*' => 'exists:enseignants,id',
        ]);

        $query = enseignants::with(['user', 'corpsEnseignant'])
            ->whereHas('user');

        if ($request->enseignant_ids) {
            $query->whereIn('id', $request->enseignant_ids);
        }

        $enseignants = $query->get();
        $moisValidite = sprintf('%d-%02d-01', $request->annee, $request->mois);
        $generes = 0;
        $ignores = 0;
        $erreurs = [];

        foreach ($enseignants as $enseignant) {
            $existe = bultins::where('enseignant_id', $enseignant->id)
                ->where('mois_validite', $moisValidite)
                ->whereIn('statut', ['genere', 'valide', 'paye'])
                ->exists();

            if ($existe) {
                $ignores++;
                continue;
            }

            try {
                $calcul = $this->calculerSalaire($enseignant, $request->mois, $request->annee);

                DB::transaction(function () use ($enseignant, $moisValidite, $calcul, $request) {
                    $reference = $this->genererReference($request->annee, $request->mois);
                    $matricule = $enseignant->user->id . '-' . str_pad($enseignant->id, 4, '0', STR_PAD_LEFT);

                    $bulletin = bultins::create([
                        'matricule' => $matricule,
                        'mois_validite' => $moisValidite,
                        'date_enregistrement' => now()->toDateString(),
                        'numero_ordre' => $reference,
                        'reference' => $reference,
                        'statut' => 'genere',
                        'salaire_brut' => $calcul['brut'],
                        'total_retenues' => $calcul['total_retenues'],
                        'net_a_payer' => $calcul['net'],
                        'enseignant_id' => $enseignant->id,
                        'ia_id' => $request->ia_id,
                    ]);

                    foreach ($calcul['lignes'] as $ligne) {
                        $rubrique = rubrique_bultins::firstOrCreate(
                            ['libelle' => $ligne['rubrique']],
                            ['type' => $ligne['type']]
                        );

                        detail_bultins::create([
                            'montant_gains' => $ligne['type'] === 'gain' ? $ligne['montant'] : 0,
                            'montant_retenus' => $ligne['type'] === 'retenue' ? $ligne['montant'] : 0,
                            'bultin_id' => $bulletin->id,
                            'rubrique_bultin_id' => $rubrique->id,
                        ]);
                    }
                });

                $generes++;
            } catch (\Exception $e) {
                $nom = $enseignant->user->nom . ' ' . $enseignant->user->prenom;
                $erreurs[] = "Erreur pour {$nom}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'message' => "{$generes} bulletin(s) généré(s), {$ignores} ignoré(s) (déjà existants).",
            'generes' => $generes,
            'ignores' => $ignores,
            'erreurs' => $erreurs,
        ]);
    }

    public function valider($id)
    {
        $bulletin = bultins::findOrFail($id);

        if ($bulletin->statut !== 'genere') {
            return response()->json(['message' => 'Seul un bulletin au statut "généré" peut être validé.'], 422);
        }

        $bulletin->update(['statut' => 'valide']);

        return response()->json(['message' => 'Bulletin validé.', 'statut' => 'valide']);
    }

    public function annuler($id)
    {
        $bulletin = bultins::findOrFail($id);

        if ($bulletin->statut === 'paye') {
            return response()->json(['message' => 'Un bulletin déjà payé ne peut pas être annulé.'], 422);
        }

        $bulletin->update(['statut' => 'annule']);

        return response()->json(['message' => 'Bulletin annulé.', 'statut' => 'annule']);
    }

    public function rechercherAgents(Request $request)
    {
        $search = $request->q;

        $query = enseignants::with(['user', 'corpsEnseignant'])
            ->whereHas('user');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        $agents = $query->limit(20)->get()->map(fn ($e) => [
            'id' => $e->id,
            'nom' => $e->user->nom,
            'prenom' => $e->user->prenom,
            'indice' => $e->indice,
            'corps' => $e->corpsEnseignant?->libelle ?? '-',
            'matricule' => $e->user->id . '-' . str_pad($e->id, 4, '0', STR_PAD_LEFT),
        ]);

        return response()->json($agents);
    }

    public function statistiques(Request $request)
    {
        $query = bultins::query();

        if ($request->mois && $request->annee) {
            $date = sprintf('%d-%02d-01', $request->annee, $request->mois);
            $query->where('mois_validite', $date);
        }

        $stats = [
            'total_bulletins' => (clone $query)->count(),
            'generes' => (clone $query)->where('statut', 'genere')->count(),
            'valides' => (clone $query)->where('statut', 'valide')->count(),
            'payes' => (clone $query)->where('statut', 'paye')->count(),
            'annules' => (clone $query)->where('statut', 'annule')->count(),
            'total_brut' => round((clone $query)->whereIn('statut', ['genere', 'valide', 'paye'])->sum('salaire_brut'), 2),
            'total_retenues' => round((clone $query)->whereIn('statut', ['genere', 'valide', 'paye'])->sum('total_retenues'), 2),
            'total_net' => round((clone $query)->whereIn('statut', ['genere', 'valide', 'paye'])->sum('net_a_payer'), 2),
        ];

        return response()->json($stats);
    }

    private function calculerSalaire(enseignants $enseignant, int $mois, int $annee): array
    {
        $lignes = [];

        $salaireBase = round($enseignant->indice * self::VALEUR_POINT, 2);
        $lignes[] = ['rubrique' => 'Salaire de base', 'type' => 'gain', 'montant' => $salaireBase];

        $corpsNom = $enseignant->corpsEnseignant?->libelle ?? '';
        $indemnites = self::INDEMNITES_PAR_CORPS[$corpsNom] ?? self::INDEMNITES_DEFAUT;

        $lignes[] = ['rubrique' => 'Indemnité de logement', 'type' => 'gain', 'montant' => $indemnites['logement']];
        $lignes[] = ['rubrique' => 'Indemnité de transport', 'type' => 'gain', 'montant' => $indemnites['transport']];

        $brut = $salaireBase + $indemnites['logement'] + $indemnites['transport'];

        $ipres = round($brut * self::TAUX_IPRES, 2);
        $ir = round($brut * self::TAUX_IR, 2);
        $css = round($brut * self::TAUX_CSS, 2);

        $lignes[] = ['rubrique' => 'Retenue IPRES', 'type' => 'retenue', 'montant' => $ipres];
        $lignes[] = ['rubrique' => 'Impôt sur le revenu', 'type' => 'retenue', 'montant' => $ir];
        $lignes[] = ['rubrique' => 'Retenue CSS', 'type' => 'retenue', 'montant' => $css];

        $totalRetenues = $ipres + $ir + $css;

        $etatPresence = EtatPresence::where('enseignant_id', $enseignant->id)
            ->where('mois', $mois)
            ->where('annee', $annee)
            ->where('statut', 'valide')
            ->first();

        if ($etatPresence) {
            if ($etatPresence->retenue_absences > 0) {
                $lignes[] = ['rubrique' => 'Retenue absences', 'type' => 'retenue', 'montant' => round($etatPresence->retenue_absences, 2)];
                $totalRetenues += $etatPresence->retenue_absences;
            }
            if ($etatPresence->retenue_retards > 0) {
                $lignes[] = ['rubrique' => 'Retenue retards', 'type' => 'retenue', 'montant' => round($etatPresence->retenue_retards, 2)];
                $totalRetenues += $etatPresence->retenue_retards;
            }
        }

        $net = round($brut - $totalRetenues, 2);

        return [
            'lignes' => $lignes,
            'brut' => round($brut, 2),
            'total_retenues' => round($totalRetenues, 2),
            'net' => $net,
            'presence' => $etatPresence ? [
                'nombre_absences' => $etatPresence->nombre_absences,
                'nombre_retards' => $etatPresence->nombre_retards,
                'retenue_absences' => $etatPresence->retenue_absences,
                'retenue_retards' => $etatPresence->retenue_retards,
            ] : null,
        ];
    }

    private function genererReference(int $annee, int $mois): string
    {
        $prefix = sprintf('BUL-%d%02d', $annee, $mois);
        $dernier = bultins::where('reference', 'like', $prefix . '%')
            ->orderByDesc('reference')
            ->value('reference');

        if ($dernier) {
            $numero = (int) substr($dernier, -5) + 1;
        } else {
            $numero = 1;
        }

        return $prefix . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    private function formatBulletin(bultins $b): array
    {
        $user = $b->enseignant?->user;
        return [
            'id' => $b->id,
            'reference' => $b->reference,
            'matricule' => $b->matricule,
            'nom' => $user ? $user->nom . ' ' . $user->prenom : '-',
            'corps' => $b->enseignant?->corpsEnseignant?->libelle ?? '-',
            'ia' => $b->ia?->libelle ?? '-',
            'mois_validite' => $b->mois_validite?->format('Y-m'),
            'salaire_brut' => round($b->salaire_brut, 2),
            'total_retenues' => round($b->total_retenues, 2),
            'net_a_payer' => round($b->net_a_payer, 2),
            'statut' => $b->statut,
            'date_enregistrement' => $b->date_enregistrement?->format('Y-m-d'),
        ];
    }

    private function formatBulletinDetail(bultins $b): array
    {
        $data = $this->formatBulletin($b);

        $data['enseignant'] = [
            'id' => $b->enseignant?->id,
            'indice' => $b->enseignant?->indice,
            'situation_matrimoniale' => $b->enseignant?->situation_matrimoniale,
            'date_recrutement' => $b->enseignant?->date_recrutement,
        ];

        $data['gains'] = [];
        $data['retenues'] = [];

        foreach ($b->details as $d) {
            $item = [
                'rubrique' => $d->rubrique?->libelle ?? '-',
                'montant' => $d->montant_gains > 0 ? round($d->montant_gains, 2) : round($d->montant_retenus, 2),
            ];

            if ($d->montant_gains > 0) {
                $data['gains'][] = $item;
            } else {
                $data['retenues'][] = $item;
            }
        }

        return $data;
    }
}
