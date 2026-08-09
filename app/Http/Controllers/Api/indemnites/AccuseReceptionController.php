<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\indemnites;

use App\Enums\indemnites\AccuseReceptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\indemnites\StoreAccuseReceptionRequest;
use App\Http\Requests\indemnites\UpdateAccuseReceptionRequest;
use App\Http\Resources\indemnites\AccuseReceptionResource;
use App\Models\indemnites\Accuse_reception;
use App\Models\indemnites\Agent;
use App\Models\indemnites\Document;
use App\Services\indemnites\AccuseReceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccuseReceptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless(
            $request->user()->hasPermission('accuses_reception.view'),
            403,
            'Accès interdit.'
        );

        $query = Accuse_reception::query()
            ->with([
                'document',
                'agentDeposant.user',
                'agentDeposant.lieuDeService',
                'agentReceptionnaire.user',
                'agentReceptionnaire.lieuDeService',
            ])
            ->nonArchives();

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($query) use ($search): void {
                $query
                    ->whereHas('document', function ($query) use ($search): void {
                        $query
                            ->where('reference', 'like', "%{$search}%")
                            ->orWhere('libelle', 'like', "%{$search}%");
                    })
                    ->orWhereHas('agentDeposant', function ($query) use ($search): void {
                        $query->where('matricule', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($query) use ($search): void {
                                $query
                                    ->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenom', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('agentDeposant.lieuDeService', function ($query) use ($search): void {
                        $query->where('libelle', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->value
            );
        }

        $accuses = $query
            ->latest('date_depot')
            ->paginate(
                min(
                    max((int) $request->input('per_page', 15), 1),
                    100
                )
            )
            ->withQueryString();

        return AccuseReceptionResource::collection($accuses);
    }

    public function store(
        StoreAccuseReceptionRequest $request,
        AccuseReceptionService $service
    ): AccuseReceptionResource {

       abort_unless(
            $request->user()->hasPermission('accuses_reception.create'),
            403,
            'Accès interdit.'
        );

        $validated = $request->validated();

        $document = Document::findOrFail(
            $validated['document_id']
        );

        $agentDeposant = Agent::findOrFail(
            $validated['agent_deposant_id']
        );

        $agentReceptionnaire = Agent::findOrFail(
            $validated['agent_receptionnaire_id']
        );

        $accuse = $service->create(
            document: $document,
            agentDeposant: $agentDeposant,
            agentReceptionnaire: $agentReceptionnaire,
            dateDepot: $validated['date_depot'],
            status: AccuseReceptionStatus::from(
                $validated['status']
            )
        );

        return new AccuseReceptionResource(
            $accuse->load([
                'document',
                'agentDeposant.user',
                'agentDeposant.lieuDeService',
                'agentReceptionnaire.user',
                'agentReceptionnaire.lieuDeService',
            ])
        );
    }

    public function show(
        Accuse_reception $accuseReception
    ): AccuseReceptionResource {

    //($accuseReception->toArray());
        abort_unless(
            request()->user()->hasPermission('accuses_reception.view'),
            403,
            'Accès interdit.'
        );

        return new AccuseReceptionResource(
            $accuseReception->load([
                'document',
                'agentDeposant.user',
                'agentDeposant.lieuDeService',
                'agentReceptionnaire.user',
                'agentReceptionnaire.lieuDeService',
            ])
        );
    }

    public function update(
        UpdateAccuseReceptionRequest $request,
        Accuse_reception $accuseReception,
        AccuseReceptionService $service
    ): AccuseReceptionResource {

        abort_unless(
            $request->user()->hasPermission('accuses_reception.update'),
            403,
            'Accès interdit.'
        );
        $validated = $request->validated();

        $document = Document::findOrFail(
            $validated['document_id']
        );

        $agentDeposant = Agent::findOrFail(
            $validated['agent_deposant_id']
        );

        $agentReceptionnaire = Agent::findOrFail(
            $validated['agent_receptionnaire_id']
        );

        $accuse = $service->update(
            accuse: $accuseReception,
            document: $document,
            agentDeposant: $agentDeposant,
            agentReceptionnaire: $agentReceptionnaire,
            dateDepot: $validated['date_depot'],
            status: AccuseReceptionStatus::from(
                $validated['status']
            )
        );

        return new AccuseReceptionResource(
            $accuse->load([
                'document',
                'agentDeposant.user',
                'agentDeposant.lieuDeService',
                'agentReceptionnaire.user',
                'agentReceptionnaire.lieuDeService',
            ])
        );
    }

    public function destroy(
        Accuse_reception $accuseReception,
        AccuseReceptionService $service
    ): JsonResponse {

        abort_unless(
            request()->user()->hasPermission('accuses_reception.delete'),
            403,
            'Accès interdit.'
        );

        $service->delete($accuseReception);

        return response()->json([
            'message' => 'Accusé de réception supprimé avec succès.',
        ]);
    }

    public function currentAgent(Request $request): JsonResponse
    {
        $agent = $request->user()
            ?->agent()
            ->with('lieuDeService')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $agent->id,
                'matricule' => $agent->matricule,
                'prenom' => $agent->user?->prenom,
                'nom' => $agent->user?->nom,
                'lieu_de_service' => [
                    'id' => $agent->lieuDeService?->id,
                    'code' => $agent->lieuDeService?->code,
                    'libelle' => $agent->lieuDeService?->libelle,
                ],
            ],
        ]);
    }

    public function agent(
        Agent $agent
    ): JsonResponse {
        $agent->load([
            'user',
            'lieuDeService',
        ]);

        return response()->json([
            'data' => [
                'id' => $agent->id,
                'matricule' => $agent->matricule,
                'prenom' => $agent->user?->prenom,
                'nom' => $agent->user?->nom,
                'lieu_de_service' => [
                    'id' => $agent->lieuDeService?->id,
                    'code' => $agent->lieuDeService?->code,
                    'libelle' => $agent->lieuDeService?->libelle,
                ],
            ],
        ]);
    }



}
