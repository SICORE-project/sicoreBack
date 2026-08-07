<?php

namespace App\Http\Controllers;

use App\Models\ServiceFait;
use App\Models\ServiceFaitHistorique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ServicesFaitsController extends Controller
{
    /**
     * Liste de tous les services faits.
     */
    public function index(Request $request)
    {
        $query = ServiceFait::with([
            'enseignant',
            'convocation',
            'utilisateur',
            'validateur',
        ]);

        if ($request->filled('enseignant_id')) {
            $query->where(
                'enseignant_id',
                $request->input('enseignant_id')
            );
        }

        if ($request->filled('convocation_id')) {
            $query->where(
                'convocation_id',
                $request->input('convocation_id')
            );
        }

        if ($request->filled('statut')) {
            $query->where(
                'statut',
                $request->input('statut')
            );
        }

        if ($request->filled('date_debut')) {
            $query->whereDate(
                'date_debut',
                '>=',
                $request->input('date_debut')
            );
        }

        if ($request->filled('date_fin')) {
            $query->whereDate(
                'date_fin',
                '<=',
                $request->input('date_fin')
            );
        }

        $perPage = $this->perPage($request);

        $servicesFaits = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des services faits récupérée avec succès.',
            'data' => $servicesFaits,
        ]);
    }

    /**
     * Créer une déclaration de service fait.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'convocation_id' => [
                'required',
                'exists:convocations,id',
            ],

            'enseignant_id' => [
                'required',
                'exists:enseignants,id',
            ],

            'date_debut' => [
                'required',
                'date',
            ],

            'date_fin' => [
                'required',
                'date',
                'after_or_equal:date_debut',
            ],

            'lieu' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'nombre_jours' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $validated['utilisateur_id'] = Auth::id();
        $validated['statut'] = 'en_attente';

        $serviceFait = ServiceFait::create($validated);

        $this->creerHistorique(
            $serviceFait,
            'creation',
            null,
            $serviceFait->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Déclaration de service fait créée avec succès.',
            'data' => $serviceFait->load([
                'enseignant',
                'convocation',
                'utilisateur',
            ]),
        ], 201);
    }

    /**
     * Consulter une déclaration.
     */
    public function show(ServiceFait $serviceFait)
    {
        $serviceFait->load([
            'enseignant',
            'convocation',
            'utilisateur',
            'validateur',
            'historiques.utilisateur',
        ]);

        return response()->json([
            'success' => true,
            'data' => $serviceFait,
        ]);
    }

    /**
     * Afficher les détails d'une prestation.
     */
    public function details(ServiceFait $serviceFait)
    {
        $serviceFait->load([
            'enseignant',
            'convocation',
            'utilisateur',
            'validateur',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Détails du service fait récupérés avec succès.',
            'data' => $serviceFait,
        ]);
    }

    /**
     * Afficher les déclarations en attente.
     */
    public function enAttente(Request $request)
    {
        $perPage = $this->perPage($request);

        $servicesFaits = ServiceFait::with([
            'enseignant',
            'convocation',
            'utilisateur',
        ])
            ->where('statut', 'en_attente')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Déclarations en attente récupérées avec succès.',
            'data' => $servicesFaits,
        ]);
    }

    /**
     * Rechercher et filtrer les services faits.
     */
    public function rechercher(Request $request)
    {
        $query = ServiceFait::with([
            'enseignant',
            'convocation',
            'utilisateur',
        ]);

        if ($request->filled('enseignant_id')) {
            $query->where(
                'enseignant_id',
                $request->input('enseignant_id')
            );
        }

        if ($request->filled('convocation_id')) {
            $query->where(
                'convocation_id',
                $request->input('convocation_id')
            );
        }

        if ($request->filled('statut')) {
            $query->where(
                'statut',
                $request->input('statut')
            );
        }

        if ($request->filled('date_debut')) {
            $query->whereDate(
                'date_debut',
                '>=',
                $request->input('date_debut')
            );
        }

        if ($request->filled('date_fin')) {
            $query->whereDate(
                'date_fin',
                '<=',
                $request->input('date_fin')
            );
        }

        if ($request->filled('periode_debut')) {
            $query->whereDate(
                'date_debut',
                '>=',
                $request->input('periode_debut')
            );
        }

        if ($request->filled('periode_fin')) {
            $query->whereDate(
                'date_fin',
                '<=',
                $request->input('periode_fin')
            );
        }

        $perPage = $this->perPage($request);

        $servicesFaits = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Recherche effectuée avec succès.',
            'data' => $servicesFaits,
        ]);
    }

    /**
     * Modifier une déclaration.
     */
    public function update(
        Request $request,
        ServiceFait $serviceFait
    ) {
        if ($serviceFait->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les déclarations en attente peuvent être modifiées.',
            ], 422);
        }

        $validated = $request->validate([
            'convocation_id' => [
                'sometimes',
                'exists:convocations,id',
            ],

            'enseignant_id' => [
                'sometimes',
                'exists:enseignants,id',
            ],

            'date_debut' => [
                'sometimes',
                'date',
            ],

            'date_fin' => [
                'sometimes',
                'date',
                'after_or_equal:date_debut',
            ],

            'lieu' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'nombre_jours' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ]);

        $anciennesValeurs = $serviceFait->toArray();

        $serviceFait->update($validated);

        $nouvellesValeurs = $serviceFait->fresh()->toArray();

        $this->creerHistorique(
            $serviceFait,
            'modification',
            $anciennesValeurs,
            $nouvellesValeurs
        );

        return response()->json([
            'success' => true,
            'message' => 'Déclaration modifiée avec succès.',
            'data' => $serviceFait->fresh()->load([
                'enseignant',
                'convocation',
                'utilisateur',
            ]),
        ]);
    }

    /**
     * Corriger une déclaration avant validation.
     */
    public function corriger(
        Request $request,
        ServiceFait $serviceFait
    ) {
        if ($serviceFait->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette déclaration ne peut plus être corrigée.',
            ], 422);
        }

        $validated = $request->validate([
            'convocation_id' => [
                'sometimes',
                'exists:convocations,id',
            ],

            'enseignant_id' => [
                'sometimes',
                'exists:enseignants,id',
            ],

            'date_debut' => [
                'sometimes',
                'date',
            ],

            'date_fin' => [
                'sometimes',
                'date',
                'after_or_equal:date_debut',
            ],

            'lieu' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'nombre_jours' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ]);

        $anciennesValeurs = $serviceFait->toArray();

        $serviceFait->update($validated);

        $nouvellesValeurs = $serviceFait->fresh()->toArray();

        $this->creerHistorique(
            $serviceFait,
            'correction',
            $anciennesValeurs,
            $nouvellesValeurs
        );

        return response()->json([
            'success' => true,
            'message' => 'Déclaration corrigée avec succès.',
            'data' => $serviceFait->fresh()->load([
                'enseignant',
                'convocation',
                'utilisateur',
            ]),
        ]);
    }

    /**
     * Vérifier la conformité d'une déclaration.
     */
    public function verifierConformite(
        ServiceFait $serviceFait
    ) {
        $erreurs = [];

        if (! $serviceFait->convocation_id) {
            $erreurs[] = 'La convocation est obligatoire.';
        }

        if (! $serviceFait->enseignant_id) {
            $erreurs[] = 'Le bénéficiaire est obligatoire.';
        }

        if (! $serviceFait->date_debut) {
            $erreurs[] = 'La date de début est obligatoire.';
        }

        if (! $serviceFait->date_fin) {
            $erreurs[] = 'La date de fin est obligatoire.';
        }

        if (
            $serviceFait->date_debut &&
            $serviceFait->date_fin &&
            Carbon::parse($serviceFait->date_fin)
                ->lt(Carbon::parse($serviceFait->date_debut))
        ) {
            $erreurs[] =
                'La date de fin doit être supérieure ou égale à la date de début.';
        }

        if (
            is_null($serviceFait->nombre_jours) ||
            $serviceFait->nombre_jours < 1
        ) {
            $erreurs[] =
                'Le nombre de jours doit être supérieur à zéro.';
        }

        return response()->json([
            'success' => count($erreurs) === 0,
            'conforme' => count($erreurs) === 0,
            'message' => count($erreurs) === 0
                ? 'La déclaration est conforme.'
                : 'La déclaration présente des erreurs.',
            'erreurs' => $erreurs,
        ]);
    }

    /**
     * Valider une déclaration.
     */
    public function valider(
        ServiceFait $serviceFait
    ) {
        if ($serviceFait->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette déclaration a déjà été traitée.',
            ], 422);
        }

        if (! $this->estConforme($serviceFait)) {
            return response()->json([
                'success' => false,
                'message' => 'La déclaration n\'est pas conforme.',
            ], 422);
        }

        $dateValidation = now();
        $utilisateurId = Auth::id();

        $serviceFait->update([
            'statut' => 'valide',
            'valide_par' => $utilisateurId,
            'valide_at' => $dateValidation,
            'motif_rejet' => null,
        ]);

        $this->creerHistorique(
            $serviceFait,
            'validation',
            null,
            [
                'statut' => 'valide',
                'valide_par' => $utilisateurId,
                'valide_at' => $dateValidation,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Service fait validé avec succès.',
            'data' => $serviceFait->fresh()->load([
                'enseignant',
                'convocation',
                'validateur',
            ]),
        ]);
    }

    /**
     * Rejeter une déclaration.
     */
    public function rejeter(
        Request $request,
        ServiceFait $serviceFait
    ) {
        if ($serviceFait->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette déclaration a déjà été traitée.',
            ], 422);
        }

        $validated = $request->validate([
            'motif_rejet' => [
                'required',
                'string',
                'min:3',
            ],
        ]);

        $dateRejet = now();
        $utilisateurId = Auth::id();

        $serviceFait->update([
            'statut' => 'rejete',
            'motif_rejet' => $validated['motif_rejet'],
            'valide_par' => $utilisateurId,
            'valide_at' => $dateRejet,
        ]);

        $this->creerHistorique(
            $serviceFait,
            'rejet',
            null,
            [
                'statut' => 'rejete',
                'motif_rejet' => $validated['motif_rejet'],
                'valide_par' => $utilisateurId,
                'valide_at' => $dateRejet,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Service fait rejeté avec succès.',
            'data' => $serviceFait->fresh()->load([
                'enseignant',
                'convocation',
                'validateur',
            ]),
        ]);
    }

    /**
     * Notifier le déclarant du résultat.
     */
    public function notifier(
        ServiceFait $serviceFait
    ) {
        if (! in_array(
            $serviceFait->statut,
            ['valide', 'rejete'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'La déclaration doit être validée ou rejetée avant notification.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Le déclarant a été notifié du résultat.',
            'statut' => $serviceFait->statut,
        ]);
    }

    /**
     * Afficher l'historique.
     */
    public function historique(
        ServiceFait $serviceFait
    ) {
        $historiques = $serviceFait
            ->historiques()
            ->with('utilisateur')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Historique récupéré avec succès.',
            'data' => $historiques,
        ]);
    }

    /**
     * Exporter l'historique.
     */
    public function export(Request $request)
    {
        $query = ServiceFaitHistorique::with([
            'serviceFait',
            'utilisateur',
        ]);

        if ($request->filled('service_fait_id')) {
            $query->where(
                'service_fait_id',
                $request->input('service_fait_id')
            );
        }

        if ($request->filled('utilisateur_id')) {
            $query->where(
                'utilisateur_id',
                $request->input('utilisateur_id')
            );
        }

        $historiques = $query
            ->latest()
            ->get();

        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, [
            'ID',
            'Service fait',
            'Utilisateur',
            'Action',
            'Date',
        ], ';');

        foreach ($historiques as $historique) {
            fputcsv($stream, [
                $historique->id,
                $historique->service_fait_id,
                $historique->utilisateur_id,
                $this->csvValue($historique->action),
                $historique->created_at,
            ], ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return Response::make(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Content-Disposition' => 'attachment; filename="historique-services-faits.csv"',
            ]
        );
    }

    /**
     * Vérifier la conformité interne.
     */
    private function estConforme(
        ServiceFait $serviceFait
    ): bool {
        if (
            ! $serviceFait->convocation_id ||
            ! $serviceFait->enseignant_id ||
            ! $serviceFait->date_debut ||
            ! $serviceFait->date_fin ||
            is_null($serviceFait->nombre_jours) ||
            $serviceFait->nombre_jours < 1
        ) {
            return false;
        }

        return Carbon::parse($serviceFait->date_fin)
            ->gte(
                Carbon::parse($serviceFait->date_debut)
            );
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }

    /**
     * Créer une entrée dans l'historique.
     */
    private function creerHistorique(
        ServiceFait $serviceFait,
        string $action,
        ?array $anciennesValeurs,
        ?array $nouvellesValeurs
    ): void {
        ServiceFaitHistorique::create([
            'service_fait_id' => $serviceFait->id,

            'utilisateur_id' => Auth::id() ?? $serviceFait->utilisateur_id,

            'action' => $action,

            'anciennes_valeurs' => $anciennesValeurs,

            'nouvelles_valeurs' => $nouvellesValeurs,
        ]);
    }
}
