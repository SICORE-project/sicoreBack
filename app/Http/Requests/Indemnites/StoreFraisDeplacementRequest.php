<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création d'une mission de déplacement (App\Models\Indemnite\MissionDeplacement).
 *
 * CORRIGÉ (2026-08-17) : beneficiaire_id validait contre `users`, alors que
 * le bénéficiaire d'une fiche de déplacement est un membre convoqué
 * (`enseignants`), pas forcément un compte SICORE — voir le correctif
 * apporté à la migration missions_deplacement. convocation_id ajouté :
 * obligatoire pour rattacher la fiche à son dossier d'origine et vérifier
 * côté contrôleur que le dossier de pièces justificatives est complet.
 */
class StoreFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'convocation_id' => ['required', 'integer', 'exists:convocations,id'],
            'beneficiaire_id' => ['required', 'integer', 'exists:enseignants,id'],
            // Champs du RECTO de la feuille de déplacement papier — voir
            // migration 2026_08_18_004500_add_feuille_deplacement_champs_...
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
            // Colonnes du tableau "Décompte des avances au départ" du
            // papier, en plus de Nombre/Taux/Décompte : remarques libres
            // couvrant tout le tableau, pas une valeur par ligne.
            'indication_requisitions' => ['nullable', 'string'],
            'poids_bagages_mobilier' => ['nullable', 'string'],
            'avance_versee' => ['nullable', 'numeric', 'min:0'],
            'arrete_somme' => ['nullable', 'string', 'max:255'],
            'date_fait_avance' => ['nullable', 'date'],
            // statut_agent / indice_agent : envoyés par le front pour
            // affichage/confirmation, mais TOUJOURS re-dérivés côté
            // contrôleur depuis l'enseignant (source de vérité) — voir
            // FraisDeplacementController::store().
            'statut_agent' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
            'indice_agent' => ['nullable', 'numeric', 'min:0'],
            // Montant saisi librement pour un contractuel (pas de barème
            // fixe ni d'indice) — ignoré pour les autres catégories.
            'montant_saisi' => ['nullable', 'numeric', 'min:0'],
            'salaire_global_annuel' => ['nullable', 'numeric', 'min:0'],
            'lieu_service' => ['nullable', 'string', 'max:255'],
            // VERSO — "DETAIL DES VISAS ET PAIEMENT SUCCESSIFS EN COURS DE
            // ROUTE" : 4 lignes fixes (comme sur le papier), un index par
            // ligne — voir FraisDeplacementController::construireVisasRoute().
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
            // "AVANCE OU COMPTE PERCUS EN ROUTE"
            'visa_avance_indemnite_normale_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_normale_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_reduite_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_reduite_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_partielle_nombre' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_indemnite_partielle_taux' => ['nullable', 'numeric', 'min:0'],
            'visa_avance_payer_somme' => ['nullable', 'string', 'max:255'],
            'visa_avance_lieu' => ['nullable', 'string', 'max:255'],
            'visa_avance_date' => ['nullable', 'date'],
            // "REGLEMENT DEFINITIF"
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
            // "OBSERVATIONS" (colonne libre tout à droite du tableau verso)
            'observations' => ['nullable', 'string'],
            
            'fichier_recto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fichier_verso' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
