<?php

namespace App\Rules;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Le rôle super administrateur est réservé au super administrateur :
 * un administrateur ne peut ni créer un super administrateur, ni se donner ce rôle,
 * ni modifier, désactiver ou supprimer un compte super administrateur.
 */
class RoleSystemeReserve implements ValidationRule
{
    private const ROLES_SYSTEME = ['super_admin', 'super_administrateur'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $roleSysteme = Role::query()->whereKey($value)->whereIn('slug', self::ROLES_SYSTEME)->exists();

        if ($roleSysteme && ! self::acteurEstSuperAdmin()) {
            $fail('Seul un super administrateur peut attribuer ce rôle.');
        }
    }

    /**
     * Un compte super administrateur ne peut être modifié que par un super administrateur.
     */
    public static function protegerCompte(?User $cible): void
    {
        $cible?->loadMissing('role');

        abort_if(
            in_array($cible?->role?->slug, self::ROLES_SYSTEME, true) && ! self::acteurEstSuperAdmin(),
            403,
            'Seul un super administrateur peut modifier ce compte.'
        );
    }

    private static function acteurEstSuperAdmin(): bool
    {
        return in_array(request()->user()?->role?->slug, self::ROLES_SYSTEME, true);
    }
}
