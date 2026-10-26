<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

/**
 * Permissions PAR DÉFAUT des profils de base. Les profils créés ensuite dans
 * l'interface reçoivent leurs permissions depuis l'écran Profils / Rôles.
 * Le seeder AJOUTE les permissions par défaut manquantes sans jamais en retirer :
 * les réglages faits dans l'interface sont conservés.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $set = fn (string $slug, array $perms) => Role::whereIn('slug', $slug === 'admin' ? ['admin', 'administrateur'] : [$slug])->first()
            ?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $perms)->pluck('id')->all());

        $groupes = fn (array $g) => Permission::whereIn('groupe', $g)->pluck('slug')->all();

        // Référentiels en lecture seule, nécessaires aux formulaires enseignants
        $referentiels = [
            'parametrage.corps.read', 'parametrage.categories.read', 'parametrage.diplomes.read',
            'parametrage.specialites.read', 'parametrage.syndicats.read',
            'parametrage.institutions_financieres.read', 'parametrage.lieux_service.read',
            'parametrage.centres_formation.read',
        ];

        // SUPER ADMIN : tout
        $set('super_admin', Permission::pluck('slug')->all());

        // ADMIN : administration, paramétrage, personnel, indemnités, paie (national)
        $set('admin', $groupes(['administration', 'parametrage', 'personnel', 'indemnites', 'paie']));

        // GESTIONNAIRE IA : enseignants et IEF de SON IA (périmètre = ia_id de l'utilisateur)
        $set('gestionnaire_ia', array_merge($referentiels, [
            'parametrage.ia.read', 'parametrage.ief.read', 'parametrage.ief.manage',
            'enseignants.read', 'enseignants.create', 'enseignants.update', 'enseignants.validate',
            'enseignants.search', 'enseignants.export', 'enseignants.comptes_bancaires.manage',
        ]));

        // DECPC : gère entièrement les convocations et les pièces justificatives
        $set('decpc', array_merge($referentiels, [
            'pieces.read', 'pieces.manage', 'pieces.validate', 'convocations.read', 'convocations.manage',
            'parametrage.ia.read', 'parametrage.ief.read',
            'enseignants.read', 'enseignants.search',
        ]));

        // ENSEIGNANT : ses propres bulletins et ses propres pièces justificatives
        $set('enseignant', ['paie.mes_bulletins.read', 'mes_pieces.read', 'mes_pieces.deposer']);
    }
}
