<?php

namespace App\Http\Controllers;

use App\Models\indemnites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndemnitesController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => indemnites::where('utilisateur_id', $request->user()->id)->latest()->paginate(min(max($request->integer('per_page', 15), 1), 100))]);
    }

    public function baremes(Request $request)
    {
        $this->admin($request);

        return response()->json(['success' => true, 'data' => DB::table('baremes_indemnites')->latest('version')->get()]);
    }

    public function creerBareme(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['type_indemnite_id' => ['required', 'exists:type_indemnites,id'], 'type_session' => ['nullable', 'string', 'max:100'], 'taux_unitaire' => ['required', 'numeric', 'min:0'], 'taux_kilometrique' => ['nullable', 'numeric', 'min:0'], 'plafond' => ['nullable', 'numeric', 'min:0'], 'date_effet' => ['required', 'date'], 'date_fin' => ['nullable', 'date', 'after_or_equal:date_effet']]);
        $data['version'] = (int) DB::table('baremes_indemnites')->where('type_indemnite_id', $data['type_indemnite_id'])->max('version') + 1;
        $data['taux_kilometrique'] = $data['taux_kilometrique'] ?? 0;
        $data['cree_par'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('baremes_indemnites')->insertGetId($data);

        return response()->json(['success' => true, 'data' => DB::table('baremes_indemnites')->find($id)], 201);
    }

    public function simuler(Request $request)
    {
        $data = $request->validate(['type_indemnite_id' => ['required', 'exists:type_indemnites,id'], 'quantite' => ['required', 'numeric', 'min:0'], 'nombre_kilometrages' => ['nullable', 'numeric', 'min:0'], 'type_session' => ['nullable', 'string', 'max:100'], 'date_calcul' => ['nullable', 'date']]);

        return response()->json(['success' => true, 'data' => $this->calcul($data)]);
    }

    public function calculer(Request $request)
    {
        $data = $request->validate(['type_indemnite_id' => ['required', 'exists:type_indemnites,id'], 'quantite' => ['required', 'numeric', 'min:0'], 'nombre_kilometrages' => ['nullable', 'numeric', 'min:0'], 'type_session' => ['nullable', 'string', 'max:100'], 'date_calcul' => ['nullable', 'date'], 'nombre_copies' => ['nullable', 'integer', 'min:0'], 'nombre_heures' => ['nullable', 'integer', 'min:0'], 'ordre_de_mission' => ['nullable', 'boolean'], 'lieu_affectation' => ['nullable', 'string', 'max:255']]);
        $calcul = $this->calcul($data);
        $indemnite = indemnites::create(array_merge($data, ['montant' => $calcul['montant_total'], 'montant_base' => $calcul['montant_base'], 'frais_deplacement' => $calcul['frais_deplacement'], 'montant_total' => $calcul['montant_total'], 'bareme_id' => $calcul['bareme_id'], 'utilisateur_id' => $request->user()->id, 'statut' => 'calcule']));

        return response()->json(['success' => true, 'data' => $indemnite], 201);
    }

    public function valider(Request $request, indemnites $indemnite)
    {
        $this->admin($request);
        abort_if($indemnite->statut === 'valide', 422, 'Calcul déjà verrouillé.');
        $data = $request->validate(['montant_total' => ['nullable', 'numeric', 'min:0'], 'commentaire' => ['nullable', 'string', 'max:2000']]);
        $total = $data['montant_total'] ?? $indemnite->montant_total;
        $indemnite->update(['montant_total' => $total, 'montant' => $total, 'statut' => 'valide', 'valide_par' => $request->user()->id, 'valide_at' => now(), 'commentaire_validation' => $data['commentaire'] ?? null]);

        return response()->json(['success' => true, 'data' => $indemnite->fresh()]);
    }

    private function calcul(array $data): array
    {
        $date = $data['date_calcul'] ?? today()->toDateString();
        $bareme = DB::table('baremes_indemnites')->where('type_indemnite_id', $data['type_indemnite_id'])->where('actif', true)->whereDate('date_effet', '<=', $date)->where(fn ($q) => $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $date))->when($data['type_session'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('type_session', $v)->orWhereNull('type_session')))->orderByDesc('version')->first();
        $taux = $bareme->taux_unitaire ?? DB::table('type_indemnites')->where('id', $data['type_indemnite_id'])->value('prix_unitaire');
        $base = round($taux * $data['quantite'], 2);
        $frais = round(($bareme->taux_kilometrique ?? 0) * ($data['nombre_kilometrages'] ?? 0), 2);
        $total = $base + $frais;
        if ($bareme?->plafond !== null) {
            $total = min($total, (float) $bareme->plafond);
        }

        return ['bareme_id' => $bareme->id ?? null, 'montant_base' => $base, 'frais_deplacement' => $frais, 'montant_total' => round($total, 2)];
    }

    private function admin(Request $request): void
    {
        abort_unless(strtolower((string) $request->user()->loadMissing('role')->role?->libelle) === 'administrateur', 403, 'Réservé aux administrateurs.');
    }
}
