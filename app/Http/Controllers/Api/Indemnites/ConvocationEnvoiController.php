<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\EnvoyerConvocationRequest;
use App\Http\Requests\Indemnites\RelancerConvocationRequest;
use App\Mail\Indemnites\ConvocationMail;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\ConvocationEnvoi;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConvocationEnvoiController extends Controller
{
    use ApiResponseTrait;

    /**
     * Envoie la convocation aux bénéficiaires (tous, ou une sélection).
     */
    public function envoyer(EnvoyerConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $enseignants = $this->resoudreBeneficiaires($convocation, $request->validated('enseignant_ids'));

        if ($enseignants->isEmpty()) {
            return $this->error('Aucun bénéficiaire à notifier pour cette convocation.', 422);
        }

        $canal = $request->validated('canal') ?? 'email';
        $message = $request->validated('message');

        $envois = $enseignants->map(function ($enseignant) use ($convocation, $canal, $message) {
            $statut = $this->tenterEnvoi($enseignant, $convocation, $canal, $message);

            return ConvocationEnvoi::create([
                'convocation_id' => $convocation->id,
                'enseignant_id' => $enseignant->id,
                'canal' => $canal,
                'statut' => $statut,
                'message' => $message,
                'date_envoi' => now(),
            ]);
        });

        $convocation->update(['statut' => 'envoyee']);

        return $this->success('Convocation envoyée.', $envois, 201);
    }

    /**
     * Relance les bénéficiaires (tous, ou une sélection) pour une convocation déjà émise.
     */
    public function relancer(RelancerConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $enseignants = $this->resoudreBeneficiaires($convocation, $request->validated('enseignant_ids'));

        if ($enseignants->isEmpty()) {
            return $this->error('Aucun bénéficiaire à relancer pour cette convocation.', 422);
        }

        $message = $request->validated('message') ?? 'Relance : merci de confirmer votre participation.';

        $envois = $enseignants->map(function ($enseignant) use ($convocation, $message) {
            $statut = $this->tenterEnvoi($enseignant, $convocation, 'email', $message);

            return ConvocationEnvoi::create([
                'convocation_id' => $convocation->id,
                'enseignant_id' => $enseignant->id,
                'canal' => 'email',
                'statut' => $statut,
                'message' => $message,
                'date_envoi' => now(),
            ]);
        });

        return $this->success('Relance effectuée.', $envois, 201);
    }

    /**
     * Historique des envois/relances pour une convocation.
     */
    public function suivi(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $envois = ConvocationEnvoi::where('convocation_id', $id)
            ->with('enseignant')
            ->latest('date_envoi')
            ->paginate(15);

        return $this->success('Suivi des envois de la convocation.', $envois);
    }

    private function resoudreBeneficiaires(ConvocationModel $convocation, ?array $enseignantIds)
    {
        if (! empty($enseignantIds)) {
            return $convocation->enseignants()->whereIn('enseignants.id', $enseignantIds)->get();
        }

        return $convocation->enseignants()->get();
    }

    /**
     * Tente l'envoi réel de la convocation.
     *
     * Seul le canal "email" est implémenté (via le Mailable ConvocationMail).
     * Les canaux "sms" et "courrier" sont acceptés par la validation mais
     * ne disposent pas d'intégration technique à ce jour : on le trace
     * honnêtement en "echec" plutôt que de mentir sur un envoi réussi.
     */
    private function tenterEnvoi($enseignant, ConvocationModel $convocation, string $canal, ?string $message): string
    {
        if ($canal !== 'email') {
            Log::info('Envoi de convocation demandé sur un canal non implémenté', [
                'canal' => $canal,
                'enseignant_id' => $enseignant->id,
            ]);

            return 'echec';
        }

        if (empty($enseignant->email)) {
            Log::warning('Échec envoi convocation : bénéficiaire sans adresse email', [
                'enseignant_id' => $enseignant->id,
            ]);

            return 'echec';
        }

        try {
            Mail::to($enseignant->email)->send(new ConvocationMail($convocation, $enseignant, $message));

            return 'envoye';
        } catch (\Throwable $e) {
            Log::warning('Échec envoi convocation par email', ['enseignant_id' => $enseignant->id, 'error' => $e->getMessage()]);

            return 'echec';
        }
    }
}
