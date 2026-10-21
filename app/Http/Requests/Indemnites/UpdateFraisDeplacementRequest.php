<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;


class UpdateFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_emploi' => ['nullable', 'string', 'max:255'],
            'lieu_depart' => ['required', 'string', 'max:255'],
            'heure_depart' => ['nullable', 'string', 'max:10'],
            'lieu_destination' => ['required', 'string', 'max:255'],
            'motif' => ['nullable', 'string', 'max:255'],
            'date_depart' => ['required', 'date'],
            'date_retour' => ['required', 'date', 'after_or_equal:date_depart'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'moyen_transport' => ['nullable', 'string', 'max:100'],
            'ordre_service_numero' => ['nullable', 'string', 'max:100'],
            'ordre_service_date' => ['nullable', 'date'],
            'ordre_service_emetteur' => ['nullable', 'string', 'max:255'],
            'accompagne_de' => ['nullable', 'string', 'max:255'],
            'groupe' => ['nullable', 'string', 'max:50'],
            'itineraire' => ['nullable', 'string'],
            'poids_bagages_kg' => ['nullable', 'numeric', 'min:0'],
            'delivre_par' => ['nullable', 'string', 'max:255'],
            'date_emission_fiche' => ['nullable', 'date'],
            'avance_frais_transport_nombre' => ['nullable', 'numeric', 'min:0'],
            'avance_frais_transport_taux' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_normale_nombre' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_normale_taux' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_reduite_nombre' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_reduite_taux' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_partielle_nombre' => ['nullable', 'numeric', 'min:0'],
            'avance_indemnite_partielle_taux' => ['nullable', 'numeric', 'min:0'],
            'indication_requisitions' => ['nullable', 'string'],
            'poids_bagages_mobilier' => ['nullable', 'string'],
            'avance_versee' => ['nullable', 'numeric', 'min:0'],
            'arrete_somme' => ['nullable', 'string', 'max:255'],
            'date_fait_avance' => ['nullable', 'date'],
            // Indice (fonctionnaire) / montant saisi (contractuel) —
            // vacataire reste fixe à 150 000 F, jamais modifiable ici (voir
            // FraisDeplacementController::update()).
            'indice_agent' => ['nullable', 'numeric', 'min:0'],
            'montant_saisi' => ['nullable', 'numeric', 'min:0'],
            'salaire_global_annuel' => ['nullable', 'numeric', 'min:0'],
            'lieu_service' => ['nullable', 'string', 'max:255'],
            // VERSO — "DETAIL DES VISAS ET PAIEMENT SUCCESSIFS EN COURS DE
            // ROUTE" (voir StoreFraisDeplacementRequest pour le détail).
            'visa_arrivee_lieu' => ['nullable', 'array'],
            'visa_arrivee_lieu.*' => ['nullable', 'string', 'max:255'],
            'visa_arrivee_date' => ['nullable', 'array'],
            'visa_arrivee_date.*' => ['nullable', 'date'],
            'visa_arrivee_heure' => ['nullable', 'array'],
            'visa_arrivee_heure.*' => ['nullable', 'string', 'max:10'],
            'visa_depart_lieu' => ['nullable', 'array'],
            'visa_depart_lieu.*' => ['nullable', 'string', 'max:255'],
            'visa_depart_date' => ['nullable', 'array'],
            'visa_depart_date.*' => ['nullable', 'date'],
            'visa_depart_heure' => ['nullable', 'array'],
            'visa_depart_heure.*' => ['nullable', 'string', 'max:10'],
            'visa_requisitions' => ['nullable', 'array'],
            'visa_requisitions.*' => ['nullable', 'string', 'max:255'],
            'visa_poids_bagages' => ['nullable', 'array'],
            'visa_poids_bagages.*' => ['nullable', 'string', 'max:100'],
            'visa_logement_nourriture' => ['nullable', 'array'],
            'visa_logement_nourriture.*' => ['nullable', 'string', 'max:255'],
            'visa_avance_indemnite_normale_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_normale_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_reduite_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_reduite_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_partielle_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_partielle_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_payer_somme' => ['nullable', 'string', 'max:255'],
            'visa_avance_lieu' => ['nullable', 'string', 'max:255'],
            'visa_avance_date' => ['nullable', 'date'],
            'reglement_indemnite_normale_nombre' => ['nullable', 'numeric', 'min:0'],
            'reglement_indemnite_normale_taux' => ['nullable', 'numeric', 'min:0'],
            'reglement_indemnite_reduite_nombre' => ['nullable', 'numeric', 'min:0'],
            'reglement_indemnite_reduite_taux' => ['nullable', 'numeric', 'min:0'],
            'reglement_indemnite_partielle_nombre' => ['nullable', 'numeric', 'min:0'],
            'reglement_indemnite_partielle_taux' => ['nullable', 'numeric', 'min:0'],
            'reglement_montant_avances' => ['nullable', 'numeric', 'min:0'],
            'reglement_arrete_somme' => ['nullable', 'string', 'max:255'],
            'reglement_lieu' => ['nullable', 'string', 'max:255'],
            'reglement_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
