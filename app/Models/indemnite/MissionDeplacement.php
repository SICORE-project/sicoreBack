<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une "fiche de déplacement" : une mission = un bénéficiaire (un membre
 * convoqué, App\Models\Parametrage\Enseignant) rattaché à UNE convocation
 * dont le dossier de pièces justificatives est complet — cf.
 * FraisDeplacementController::beneficiairesEligibles().
 *
 * statut_agent / indice_agent sont un instantané au moment de la création
 * de la fiche (récupérés depuis l'enseignant, pas ressaisis) : fige le
 * contexte utilisé pour le calcul même si la fiche de l'enseignant change
 * ensuite.
 */
class MissionDeplacement extends Model
{
    protected $table = 'missions_deplacement';

    protected $fillable = [
        'reference',
        'convocation_id',
        'beneficiaire_id',
        'declare_par',
        'grade_emploi',
        'lieu_depart',
        'heure_depart',
        'lieu_destination',
        'motif',
        'date_depart',
        'date_retour',
        'distance_km',
        'moyen_transport',
        'ordre_service_numero',
        'ordre_service_date',
        'ordre_service_emetteur',
        'accompagne_de',
        'groupe',
        'itineraire',
        'poids_bagages_kg',
        'delivre_par',
        'date_emission_fiche',
        'avance_frais_transport_nombre',
        'avance_frais_transport_taux',
        'avance_indemnite_normale_nombre',
        'avance_indemnite_normale_taux',
        'avance_indemnite_reduite_nombre',
        'avance_indemnite_reduite_taux',
        'avance_indemnite_partielle_nombre',
        'avance_indemnite_partielle_taux',
        'avance_total',
        'arrete_somme',
        'avance_versee',
        'date_fait_avance',
        'statut_agent',
        'indice_agent',
        'salaire_global_annuel',
        'lieu_service',
        'statut',
        'montant_calcule',
        'montant_approuve',
        'valide_par',
        'valide_at',
        'motif_rejet',
        'echeance_paiement',
        'rembourse_le',
        'rembourse_par',
        'relance_at',
        'notification_at',
        'notification_message',
    ];

    protected $casts = [
        'date_depart' => 'date',
        'date_retour' => 'date',
        'ordre_service_date' => 'date',
        'date_emission_fiche' => 'date',
        'date_fait_avance' => 'date',
        'distance_km' => 'decimal:2',
        'poids_bagages_kg' => 'decimal:2',
        'avance_frais_transport_nombre' => 'decimal:2',
        'avance_frais_transport_taux' => 'decimal:2',
        'avance_indemnite_normale_nombre' => 'decimal:2',
        'avance_indemnite_normale_taux' => 'decimal:2',
        'avance_indemnite_reduite_nombre' => 'decimal:2',
        'avance_indemnite_reduite_taux' => 'decimal:2',
        'avance_indemnite_partielle_nombre' => 'decimal:2',
        'avance_indemnite_partielle_taux' => 'decimal:2',
        'avance_total' => 'decimal:2',
        'avance_versee' => 'decimal:2',
        'indice_agent' => 'decimal:2',
        'salaire_global_annuel' => 'decimal:2',
        'montant_calcule' => 'decimal:2',
        'montant_approuve' => 'decimal:2',
        'valide_at' => 'datetime',
        'echeance_paiement' => 'date',
        'rembourse_le' => 'datetime',
        'relance_at' => 'datetime',
        'notification_at' => 'datetime',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class, 'convocation_id');
    }

    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class, 'beneficiaire_id');
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'declare_par');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'valide_par');
    }

    public function rembourseur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'rembourse_par');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFraisDeplacement::class, 'mission_id');
    }

    public function justificatifs(): HasMany
    {
        return $this->hasMany(JustificatifFraisDeplacement::class, 'mission_id');
    }
}
