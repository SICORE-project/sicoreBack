<?php

namespace App\Models\Administration;

use App\Models\Parametrage\LieuService;

/**
 * Alias métier conservé pour la compatibilité de l'API d'administration.
 * La source de vérité est la table lieu_de_services.
 */
class StructureOrganisationnelle extends LieuService
{
    protected $table = 'lieu_de_services';

    public function scopeActive($query)
    {
        return $query->where('est_actif', true);
    }
}