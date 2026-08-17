<?php

namespace App\Models\Indemnite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Piece_justificatives extends Model
{
    /**
     * Types de documents attendus pour le dossier d'un membre (bouton
     * "Ajouter une pièce" sur la fiche d'un membre, page Pieces
     * justificatives) — "dossier_convocation" est particulier : il n'est
     * jamais téléversé manuellement, il est rattaché automatiquement au PDF
     * déjà généré par ConvocationPdfController (voir
     * PieceJustificativesController::attacherDossierConvocation()).
     */
    public const TYPES = [
        'service_fait' => 'Service fait',
        'ordre_mission' => 'Ordre de mission',
        'rapport_mission' => 'Rapport de mission',
        'bulletin_salaire' => 'Bulletin de salaire',
        'accuse_reception' => 'Accusé de réception',
        'dossier_convocation' => 'Dossier de convocation',
    ];

    protected $table = 'piece_justificatives';

    protected $fillable = [
        'type', 'document_url', 'date_depot', 'statut', 'dossier_complet',
        'convocation_id', 'enseignant_id', 'centre_id', 'nom_original', 'chemin', 'mime_type', 'taille',
        'depositaire_id', 'verifie_par', 'verifie_at', 'conforme',
        'commentaire_verification', 'valide_par', 'valide_at',
        'commentaire_rejet', 'notification_at', 'notification_message',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'dossier_complet' => 'boolean',
        'conforme' => 'boolean',
        'verifie_at' => 'datetime',
        'valide_at' => 'datetime',
        'notification_at' => 'datetime',
        'taille' => 'integer',
    ];

    public function convocation(): BelongsTo
    {
        return $this->belongsTo(Convocations::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parametrage\Enseignant::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ConvocationCentre::class, 'centre_id');
    }

    public function depositaire(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'depositaire_id');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'verifie_par');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'valide_par');
    }
}
