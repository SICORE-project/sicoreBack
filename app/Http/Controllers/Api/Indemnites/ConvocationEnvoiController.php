<?php

namespace App\Http\Controllers;

use App\Mail\ConvocationMail;
use App\Models\ConvocationEnvoi;
use App\Models\Convocations;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ConvocationEnvoiController extends Controller
{
    /**
     * ================================================================
     * ÉTAPE 4 — HISTORIQUE DES ENVOIS
     * ================================================================
     *
     * GET /api/convocations/{convocation}/envois
     */
    public function index($convocation)
    {
        $convocationModel = Convocations::findOrFail($convocation);

        $envois = ConvocationEnvoi::with('enseignant')
            ->where('convocation_id', $convocationModel->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historique des envois récupéré avec succès.',
            'data' => $envois,
        ], 200);
    }

    /**
     * ================================================================
     * ÉTAPE 4 — CHOISIR LE CANAL D'ENVOI
     * ================================================================
     *
     * POST /api/convocations/{convocation}/send
     *
     * Body :
     * {
     *     "canal": "email"
     * }
     */
    public function send(Request $request, $convocation)
    {
        $validated = $request->validate([
            'canal' => [
                'required',
                'in:email,notification',
            ],
        ]);

        if ($validated['canal'] === 'email') {
            return $this->sendEmail($convocation);
        }

        return $this->sendNotification($convocation);
    }

    /**
     * ================================================================
     * ÉTAPE 4 — ENVOI PAR EMAIL
     * ================================================================
     *
     * POST /api/convocations/{convocation}/send/email
     */
    public function sendEmail($convocation)
    {
        $convocationModel = Convocations::with('enseignants')
            ->findOrFail($convocation);

        if ($convocationModel->enseignants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bénéficiaire n’est affecté à cette convocation.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Générer le PDF une seule fois
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'convocations.pdf',
            [
                'convocation' => $convocationModel,
            ]
        );

        $pdfContent = $pdf->output();

        $envois = [];

        /*
        |--------------------------------------------------------------------------
        | Envoyer à chaque bénéficiaire
        |--------------------------------------------------------------------------
        */

        foreach ($convocationModel->enseignants as $enseignant) {

            /*
            |--------------------------------------------------------------------------
            | Vérifier l'adresse email
            |--------------------------------------------------------------------------
            */

            if (empty($enseignant->email)) {

                $envoi = ConvocationEnvoi::updateOrCreate(
                    [
                        'convocation_id' => $convocationModel->id,
                        'enseignant_id' => $enseignant->id,
                        'canal' => 'email',
                    ],
                    [
                        'statut' => 'echoue',
                        'message' => 'Échec de l’envoi : aucune adresse email renseignée.',
                        'date_envoi' => null,
                    ]
                );

                $envois[] = $envoi;

                continue;
            }

            try {

                /*
                |--------------------------------------------------------------------------
                | Envoi réel de l'email
                |--------------------------------------------------------------------------
                */

                Mail::to($enseignant->email)->send(
                    new ConvocationMail(
                        $convocationModel,
                        $enseignant,
                        $pdfContent
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Journaliser la réussite
                |--------------------------------------------------------------------------
                */

                $envoi = ConvocationEnvoi::updateOrCreate(
                    [
                        'convocation_id' => $convocationModel->id,
                        'enseignant_id' => $enseignant->id,
                        'canal' => 'email',
                    ],
                    [
                        'statut' => 'envoye',
                        'message' => 'Convocation envoyée par email avec succès.',
                        'date_envoi' => now(),
                    ]
                );

                $envois[] = $envoi;

                Log::info(
                    'Convocation envoyée par email.',
                    [
                        'convocation_id' => $convocationModel->id,
                        'enseignant_id' => $enseignant->id,
                        'email' => $enseignant->email,
                    ]
                );

            } catch (Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Journaliser l'erreur
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Échec de l’envoi de la convocation.',
                    [
                        'convocation_id' => $convocationModel->id,
                        'enseignant_id' => $enseignant->id,
                        'email' => $enseignant->email,
                        'error' => $e->getMessage(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Enregistrer l'échec
                |--------------------------------------------------------------------------
                */

                $envoi = ConvocationEnvoi::updateOrCreate(
                    [
                        'convocation_id' => $convocationModel->id,
                        'enseignant_id' => $enseignant->id,
                        'canal' => 'email',
                    ],
                    [
                        'statut' => 'echoue',
                        'message' => 'Échec de l’envoi : '.$e->getMessage(),
                        'date_envoi' => null,
                    ]
                );

                $envois[] = $envoi;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Statistiques
        |--------------------------------------------------------------------------
        */

        $nombreEnvoyes = collect($envois)
            ->where('statut', 'envoye')
            ->count();

        $nombreEchecs = collect($envois)
            ->where('statut', 'echoue')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Mise à jour du statut global
        |--------------------------------------------------------------------------
        */

        if ($nombreEnvoyes > 0) {
            $convocationModel->update([
                'statut' => 'envoyee',
            ]);
        }

        return response()->json([
            'success' => $nombreEnvoyes > 0,

            'message' => $nombreEnvoyes > 0
                ? 'Traitement de l’envoi terminé.'
                : 'Aucune convocation n’a pu être envoyée.',

            'statistiques' => [
                'total' => count($envois),
                'envoyes' => $nombreEnvoyes,
                'echecs' => $nombreEchecs,
            ],

            'data' => $envois,
        ], 200);
    }

    /**
     * ================================================================
     * ÉTAPE 4 — ENVOI PAR NOTIFICATION
     * ================================================================
     *
     * POST /api/convocations/{convocation}/send/notification
     */
    public function sendNotification($convocation)
    {
        $convocationModel = Convocations::with('enseignants')
            ->findOrFail($convocation);

        if ($convocationModel->enseignants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bénéficiaire n’est affecté à cette convocation.',
            ], 422);
        }

        $envois = [];

        foreach ($convocationModel->enseignants as $enseignant) {

            /*
            |--------------------------------------------------------------------------
            | Pour le moment, on journalise la notification.
            |--------------------------------------------------------------------------
            */

            $envoi = ConvocationEnvoi::updateOrCreate(
                [
                    'convocation_id' => $convocationModel->id,
                    'enseignant_id' => $enseignant->id,
                    'canal' => 'notification',
                ],
                [
                    'statut' => 'envoye',
                    'message' => 'Convocation envoyée par notification.',
                    'date_envoi' => now(),
                ]
            );

            $envois[] = $envoi;
        }

        $convocationModel->update([
            'statut' => 'envoyee',
        ]);

        Log::info(
            'Convocation traitée par notification.',
            [
                'convocation_id' => $convocationModel->id,
                'nombre_beneficiaires' => count($envois),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Traitement des notifications terminé.',
            'data' => $envois,
        ], 200);
    }

    /**
     * ================================================================
     * ÉTAPE 5 — SUIVI GLOBAL DES CONVOCATIONS
     * ================================================================
     *
     * GET /api/convocations/suivi
     *
     * Affiche tous les envois avec :
     * - convocation
     * - bénéficiaire
     * - canal
     * - statut
     * - date d'envoi
     */
    public function suivi()
    {
        $envois = ConvocationEnvoi::with([
            'convocation',
            'enseignant',
        ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Suivi des convocations récupéré avec succès.',
            'data' => $envois,
        ], 200);
    }

    /**
     * ================================================================
     * ÉTAPE 5 — FILTRER LE SUIVI
     * ================================================================
     *
     * GET /api/convocations/suivi/filtrer
     *
     * Filtres disponibles :
     *
     * ?statut=envoye
     * ?statut=echoue
     * ?statut=en_attente
     * ?canal=email
     * ?canal=notification
     * ?enseignant_id=1
     * ?convocation_id=1
     *
     * Exemple :
     *
     * /api/convocations/suivi/filtrer?statut=envoye&canal=email
     */
    public function filtrer(Request $request)
    {
        $validated = $request->validate([
            'statut' => [
                'nullable',
                'in:en_attente,envoye,echoue',
            ],

            'canal' => [
                'nullable',
                'in:email,notification',
            ],

            'enseignant_id' => [
                'nullable',
                'integer',
                'exists:enseignants,id',
            ],

            'convocation_id' => [
                'nullable',
                'integer',
                'exists:convocations,id',
            ],
        ]);

        $query = ConvocationEnvoi::with([
            'convocation',
            'enseignant',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Filtrer par statut
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['statut'])) {
            $query->where(
                'statut',
                $validated['statut']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtrer par canal
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['canal'])) {
            $query->where(
                'canal',
                $validated['canal']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtrer par bénéficiaire
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['enseignant_id'])) {
            $query->where(
                'enseignant_id',
                $validated['enseignant_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtrer par convocation
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['convocation_id'])) {
            $query->where(
                'convocation_id',
                $validated['convocation_id']
            );
        }

        $envois = $query
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Suivi filtré récupéré avec succès.',
            'filtres' => $validated,
            'data' => $envois,
        ], 200);
    }

    /**
     * ================================================================
     * ÉTAPE 5 — RELANCER UNE CONVOCATION
     * ================================================================
     *
     * POST /api/convocations/{convocation}/relancer
     *
     * Body :
     * {
     *     "canal": "email"
     * }
     *
     * La relance concerne uniquement les envois
     * qui sont en attente ou qui ont échoué.
     */
    public function relancer(Request $request, $convocation)
    {
        $validated = $request->validate([
            'canal' => [
                'required',
                'in:email,notification',
            ],
        ]);

        $convocationModel = Convocations::with('enseignants')
            ->findOrFail($convocation);

        /*
        |--------------------------------------------------------------------------
        | Vérifier les bénéficiaires
        |--------------------------------------------------------------------------
        */

        if ($convocationModel->enseignants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bénéficiaire n’est affecté à cette convocation.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Trouver les envois à relancer
        |--------------------------------------------------------------------------
        */

        $envoisArelancer = ConvocationEnvoi::where(
            'convocation_id',
            $convocationModel->id
        )
            ->where(
                'canal',
                $validated['canal']
            )
            ->whereIn(
                'statut',
                [
                    'en_attente',
                    'echoue',
                ]
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Aucun envoi à relancer
        |--------------------------------------------------------------------------
        */

        if ($envoisArelancer->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune convocation en attente ou en échec à relancer.',
                'data' => [],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Relance par email
        |--------------------------------------------------------------------------
        */

        if ($validated['canal'] === 'email') {

            $pdf = Pdf::loadView(
                'convocations.pdf',
                [
                    'convocation' => $convocationModel,
                ]
            );

            $pdfContent = $pdf->output();

            $resultats = [];

            foreach ($envoisArelancer as $ancienEnvoi) {

                $enseignant = $ancienEnvoi->enseignant;

                if (! $enseignant || empty($enseignant->email)) {

                    $ancienEnvoi->update([
                        'statut' => 'echoue',
                        'message' => 'Relance impossible : aucune adresse email renseignée.',
                    ]);

                    $resultats[] = $ancienEnvoi;

                    continue;
                }

                try {

                    Mail::to($enseignant->email)->send(
                        new ConvocationMail(
                            $convocationModel,
                            $enseignant,
                            $pdfContent
                        )
                    );

                    $ancienEnvoi->update([
                        'statut' => 'envoye',
                        'message' => 'Convocation relancée et envoyée par email avec succès.',
                        'date_envoi' => now(),
                    ]);

                } catch (Throwable $e) {

                    Log::error(
                        'Échec de la relance de la convocation.',
                        [
                            'convocation_id' => $convocationModel->id,
                            'enseignant_id' => $enseignant->id,
                            'error' => $e->getMessage(),
                        ]
                    );

                    $ancienEnvoi->update([
                        'statut' => 'echoue',
                        'message' => 'Échec de la relance : '.$e->getMessage(),
                        'date_envoi' => null,
                    ]);
                }

                $resultats[] = $ancienEnvoi->fresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Relance des convocations terminée.',
                'data' => $resultats,
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | Relance par notification
        |--------------------------------------------------------------------------
        */

        foreach ($envoisArelancer as $envoi) {

            $envoi->update([
                'statut' => 'envoye',
                'message' => 'Convocation relancée par notification.',
                'date_envoi' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Relance des notifications terminée.',
            'data' => $envoisArelancer->fresh(),
        ], 200);
    }
}
