<?php

namespace App\Models\Indemnite;

use App\Helpers\Indemnites\HasOpaqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un métier/spécialité couvert par un centre d'examen (un centre peut en
 * couvrir plusieurs — cf. modèle papier "convocation jury"). Les membres
 * du jury sont rattachés à un métier précis via
 * convocation_enseignant.centre_metier_id.
 */
class ConvocationCentreMetier extends Model
{
    use HasOpaqueSlug;

    protected $table = 'convocation_centre_metiers';

    protected $fillable = [
        'convocation_centre_id',
        'metier',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ConvocationCentre::class, 'convocation_centre_id');
    }

    /**
     * NE PAS ajouter 'categorie_personnel' à withPivot() : c'est un
     * attribut de l'ENSEIGNANT (colonne sur `enseignants`), pas de cette
     * ligne pivot — même bug déjà corrigé sur Convocations::enseignants()
     * (voir son commentaire). La colonne n'existe pas sur le pivot
     * `convocation_enseignant` ; l'ajouter ici casse tout eager-load
     * "centres.metiers.enseignants" — utilisé entre autres par
     * ConvocationPdfController::generer() — ce qui a empêché la génération
     * du PDF de convocation et donc l'auto-rattachement du "dossier de
     * convocation" (6e pièce justificative) : un dossier de bénéficiaire
     * restait bloqué "incomplet" à vie même une fois les 5 pièces
     * manuelles déposées, empêchant la création de sa fiche de déplacement.
     */
    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Parametrage\Enseignant::class,
            'convocation_enseignant',
            'centre_metier_id',
            'enseignant_id'
        )->withPivot('fonction', 'centre_id', 'provenance')->withTimestamps();
    }
}
