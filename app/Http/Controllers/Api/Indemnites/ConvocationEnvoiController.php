<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\EnvoyerConvocationRequest;
use App\Http\Requests\Indemnites\RelancerConvocationRequest;
use App\Mail\Indemnites\ConvocationMail;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\ConvocationEnvoi;
use App\Models\Parametrage\Enseignant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * "En tant qu'utilisateur, je veux envoyer une convocation aux
 * bénéficiaires afin de les informer officiellement." — le corps de cette
 * classe était vide alors que les 3 routes (envoyer/relancer/suivi)
 * étaient déjà déclarées dans routes/modules/indemnites.php : rien ne
 * fonctionnait malgré les apparences (ConvocationMail avait en plus de
 * mauvais namespaces, corrigés séparément).
 */
class ConvocationEnvoiController extends Controller
{
    use ApiResponseTrait;

    /**
     * Envoie la convocation par e-mail à ses bénéficiaires — tous par
     * défaut, ou seulement enseignant_ids si fournis. Un ConvocationEnvoi
     * est enregistré pour CHAQUE tentative (succès ou échec), pour
     * alimenter suivi()/relancer() ci-dessous. Si au moins un envoi
     * réussit, la convocation passe au statut "envoyee" (sauf si déjà
     * clôturée — on ne rétrograde pas un dossier fermé).
     */
    public function envoyer(EnvoyerConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::with('typeConvocation')->slugOuId($id)->first();

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $enseignants = $this->resoudreDestinataires($convocation, $request->validated('enseignant_ids'));

        if ($enseignants->isEmpty()) {
            return $this->error('Aucun bénéficiaire à convoquer sur cette convocation.', 422);
        }

        $resultat = $this->envoyerA($convocation, $enseignants, $request->validated('message'));

        return $this->success(
            "{$resultat['envoyes']} envoi(s) réussi(s), {$resultat['echecs']} échec(s).",
            $resultat
        );
    }

    /**
     * Renvoie la convocation aux bénéficiaires dont la DERNIÈRE tentative
     * d'envoi est en échec (ou seulement enseignant_ids si fournis —
     * relance ciblée, même si la personne n'est pas en échec).
     */
    public function relancer(RelancerConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::with('typeConvocation')->slugOuId($id)->first();

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $enseignantIdsDemandes = $request->validated('enseignant_ids');

        if ($enseignantIdsDemandes) {
            $enseignants = $convocation->enseignants()->whereIn('id', $enseignantIdsDemandes)->get();
        } else {
            $idsEnEchec = $this->beneficiairesEnEchec($convocation);
            $enseignants = $idsEnEchec
                ? $convocation->enseignants()->whereIn('id', $idsEnEchec)->get()
                : new Collection();
        }

        if ($enseignants->isEmpty()) {
            return $this->error('Aucun envoi en échec à relancer pour cette convocation.', 422);
        }

        $resultat = $this->envoyerA($convocation, $enseignants, $request->validated('message'));

        return $this->success(
            "{$resultat['envoyes']} relance(s) réussie(s), {$resultat['echecs']} échec(s).",
            $resultat
        );
    }

    /**
     * Historique des envois + stats rapides, pour la page "Suivi des
     * envois" (suivi.blade.php côté front).
     */
    public function suivi(string $id)
    {
        $convocation = ConvocationModel::slugOuId($id)->first();

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $envois = ConvocationEnvoi::with('enseignant')
            ->where('convocation_id', $convocation->id)
            ->orderByDesc('date_envoi')
            ->orderByDesc('id')
            ->get();

        return $this->success('Suivi des envois.', [
            'stats' => [
                'total' => $envois->count(),
                'envoye' => $envois->where('statut', 'envoye')->count(),
                'echec' => $envois->where('statut', 'echec')->count(),
            ],
            'envois' => $envois,
        ]);
    }

    /**
     * Destinataires d'un envoi : enseignant_ids demandés explicitement, ou
     * TOUS les bénéficiaires actuels de la convocation par défaut.
     */
    private function resoudreDestinataires(ConvocationModel $convocation, ?array $enseignantIds): Collection
    {
        if ($enseignantIds) {
            return $convocation->enseignants()->whereIn('id', $enseignantIds)->get();
        }

        return $convocation->enseignants()->get();
    }

    /**
     * Id des bénéficiaires dont la DERNIÈRE tentative d'envoi (date_envoi
     * la plus récente) est en échec — un succès plus récent qu'un échec
     * ne doit pas redéclencher de relance automatique.
     */
    private function beneficiairesEnEchec(ConvocationModel $convocation): array
    {
        $dernierParEnseignant = ConvocationEnvoi::where('convocation_id', $convocation->id)
            ->orderByDesc('date_envoi')
            ->orderByDesc('id')
            ->get()
            ->unique('enseignant_id');

        return $dernierParEnseignant->where('statut', 'echec')->pluck('enseignant_id')->all();
    }

    /**
     * Envoie effectivement l'e-mail à chaque enseignant et enregistre un
     * ConvocationEnvoi par tentative (succès ou échec) — jamais
     * d'exception qui remonte au client : un bénéficiaire sans e-mail ou
     * un envoi qui échoue ne doit pas empêcher les autres.
     */
    private function envoyerA(ConvocationModel $convocation, Collection $enseignants, ?string $messagePersonnalise): array
    {
        $envoyes = 0;
        $echecs = 0;

        foreach ($enseignants as $enseignant) {
            /** @var Enseignant $enseignant */
            if (empty($enseignant->email)) {
                ConvocationEnvoi::create([
                    'convocation_id' => $convocation->id,
                    'enseignant_id' => $enseignant->id,
                    'canal' => 'email',
                    'statut' => 'echec',
                    'message' => 'Aucune adresse e-mail renseignée pour ce bénéficiaire.',
                    'date_envoi' => now(),
                ]);

                $echecs++;

                continue;
            }

            try {
                Mail::to($enseignant->email)->send(new ConvocationMail($convocation, $enseignant, $messagePersonnalise));

                ConvocationEnvoi::create([
                    'convocation_id' => $convocation->id,
                    'enseignant_id' => $enseignant->id,
                    'canal' => 'email',
                    'statut' => 'envoye',
                    'message' => $messagePersonnalise,
                    'date_envoi' => now(),
                ]);

                $envoyes++;
            } catch (\Throwable $e) {
                Log::error('Échec envoi convocation par e-mail', [
                    'convocation_id' => $convocation->id,
                    'enseignant_id' => $enseignant->id,
                    'erreur' => $e->getMessage(),
                ]);

                ConvocationEnvoi::create([
                    'convocation_id' => $convocation->id,
                    'enseignant_id' => $enseignant->id,
                    'canal' => 'email',
                    'statut' => 'echec',
                    'message' => "Échec de l'envoi : ".$e->getMessage(),
                    'date_envoi' => now(),
                ]);

                $echecs++;
            }
        }

        // "informer officiellement" : une fois au moins un envoi reussi,
        // la convocation n'est plus un brouillon — sauf si deja cloturee
        // (on ne rouvre pas un dossier ferme).
        if ($envoyes > 0 && $convocation->statut !== 'cloturee') {
            $convocation->update(['statut' => 'envoyee']);
        }

        return ['envoyes' => $envoyes, 'echecs' => $echecs];
    }
}
