<?php

namespace App\Policies;

use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;

class EnseignantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enseignants.view');
    }

    public function view(User $user, Enseignant $enseignant): bool
    {
        if (!$user->hasPermission('enseignants.view')) {
            return false;
        }

        // Gestionnaire IA : accès uniquement à son IA
        if ($user->hasRole('gestionnaire_ia')) {
            return $enseignant->ia_id === $user->ia_id;
        }

        // Gestionnaire IEF : accès uniquement à son IEF
        if ($user->hasRole('gestionnaire_ief')) {
            return $enseignant->ief_id === $user->ief_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('enseignants.create');
    }

    public function update(User $user, Enseignant $enseignant): bool
    {
        if (!$user->hasPermission('enseignants.update')) {
            return false;
        }

        if ($user->hasRole('gestionnaire_ia')) {
            return $enseignant->ia_id === $user->ia_id;
        }

        if ($user->hasRole('gestionnaire_ief')) {
            return $enseignant->ief_id === $user->ief_id;
        }

        return true;
    }

    public function delete(User $user, Enseignant $enseignant): bool
    {
        if (!$user->hasPermission('enseignants.delete')) {
            return false;
        }

        // Seul admin ou DRH peut supprimer
        return $user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('drh');
    }

    public function validate(User $user, Enseignant $enseignant): bool
    {
        if (!$user->hasPermission('enseignants.validate')) {
            return false;
        }

        if ($user->hasRole('gestionnaire_ia')) {
            return $enseignant->ia_id === $user->ia_id;
        }

        if ($user->hasRole('gestionnaire_ief')) {
            return $enseignant->ief_id === $user->ief_id;
        }

        return $user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('drh');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('enseignants.export');
    }

    public function search(User $user): bool
    {
        return $user->hasPermission('enseignants.search');
    }

    public function deactivate(User $user, Enseignant $enseignant): bool
    {
        if (!$user->hasPermission('enseignants.deactivate')) {
            return false;
        }

        if ($user->hasRole('gestionnaire_ia')) {
            return $enseignant->ia_id === $user->ia_id;
        }

        if ($user->hasRole('gestionnaire_ief')) {
            return $enseignant->ief_id === $user->ief_id;
        }

        return true;
    }
}