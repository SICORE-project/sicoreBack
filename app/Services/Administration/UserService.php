<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;

class UserService
{

    /**
     * Création d'un utilisateur
     */
    public function create(array $data): User
    {

        // Hash du mot de passe
        $data['password'] = Hash::make($data['password']);


        return User::create($data)->load(['role', 'lieuService']);
    }

    /**
     * Liste des utilisateurs
     */
    public function all(?string $structureType = null)
    {
        return User::with(['role', 'lieuService'])
            ->when($structureType, function ($query, string $type) {
                $query->whereHas('lieuService', function ($structureQuery) use ($type) {
                    $structureQuery->whereRaw('UPPER(type) = ?', [mb_strtoupper($type)]);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Liste paginée des utilisateurs.
     */
    public function paginate(int $perPage = 10, ?string $structureType = null, array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['role', 'lieuService', 'enseignant.ia', 'enseignant.ief', 'enseignant.lieuService', 'ia', 'ief', 'lieuService.ia', 'lieuService.ief']);
        if ($structureType) $query->whereHas('lieuService', fn ($q) => $q->whereRaw('UPPER(type) = ?', [mb_strtoupper($structureType)]));
        foreach (preg_split('/\s+/u', trim($filters['nom'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(fn ($q) => $q->whereLike('nom', '%'.$term.'%')->orWhereLike('prenom', '%'.$term.'%'));
        }
        if (! empty($filters['matricule'])) $query->whereHas('enseignant', fn ($q) => $q->whereLike('matricule', '%'.$filters['matricule'].'%'));
        $location = array_filter([
            'ia_id' => $filters['ia_id'] ?? null,
            'ief_id' => $filters['ief_id'] ?? null,
            'lieu_service_id' => $filters['etablissement_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        if ($location) {
            $query->where(function ($q) use ($location) {
                // Match the whole location on one source, never mix different assignments.
                $q->whereHas('enseignant', function ($teacher) use ($location) {
                    foreach ($location as $column => $value) $teacher->where($column, $value);
                })->orWhere(function ($account) use ($location) {
                    $account->whereNull('enseignant_id')->where(function ($assignment) use ($location) {
                        $assignment->where(function ($direct) use ($location) {
                            foreach ($location as $column => $value) $direct->where($column, $value);
                        })->orWhereHas('lieuService', function ($structure) use ($location) {
                            foreach ($location as $column => $value) $structure->where($column === 'lieu_service_id' ? 'id' : $column, $value);
                        });
                    });
                });
            });
        }
        return $query->orderBy('nom')->orderBy('prenom')->orderBy('id')->paginate($perPage);
    }



    /**
     * Trouver un utilisateur
     */
    public function find(int $id): User
    {
        return User::with(['role', 'lieuService'])
            ->findOrFail($id);
    }

    /**
     * Mise à jour utilisateur
     */
    public function update(User $user, array $data): User
    {

        if(isset($data['password'])){

            $data['password'] = Hash::make(
                $data['password']
            );

        }

        $user->update($data);

        $user->load(['role', 'lieuService']);

        return $user;
    }

    /**
     * Suppression utilisateur
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }
    

    /**
 * Rattacher un utilisateur à une IA
 */
public function assignUserToIa(int $userId, int $iaId): User
{
    // 1. Trouver l'utilisateur
    $user = User::findOrFail($userId);
    
    // 2. ✅ VÉRIFIER QUE L'IA EXISTE
    $ia = Ia::findOrFail($iaId);  // ← Cette ligne vérifie l'existence
    
    // 3. Vérifier que l'utilisateur a le bon rôle
    if (!$user->hasRole('gestionnaire_ia')) {
        throw new \Exception("Cet utilisateur n'a pas le rôle Gestionnaire IA.");
    }

    // 4. Vérifier s'il est déjà rattaché
    if ($user->ia_id) {
        throw new \Exception("Cet utilisateur est déjà rattaché à l'IA : {$user->ia->libelle}");
    }

    // 5. Rattacher
    $user->ia_id = $iaId;
    $user->save();

    return $user->load(['ia', 'role']);
}

public function revokeUserFromIa(int $userId): User
{
    $user = User::findOrFail($userId);
    $user->update(['ia_id' => null]);

    return $user->load(['ia', 'role']);
}

public function assignUserToIef(int $userId, int $iefId): User
{
    $user = User::findOrFail($userId);
    Ief::findOrFail($iefId);

    if (! $user->hasRole('gestionnaire_ief')) {
        throw new \Exception("Cet utilisateur n'a pas le rôle Gestionnaire IEF.");
    }

    if ($user->ief_id) {
        throw new \Exception('Cet utilisateur est déjà rattaché à une IEF.');
    }

    $user->update(['ief_id' => $iefId]);

    return $user->load(['ief', 'role']);
}

public function revokeUserFromIef(int $userId): User
{
    $user = User::findOrFail($userId);
    $user->update(['ief_id' => null]);

    return $user->load(['ief', 'role']);
}

public function getUserIef(int $userId)
{
    return User::with('ief')->findOrFail($userId)->ief;
}

public function getGestionnairesIef()
{
    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->with(['ief', 'role'])
        ->get();
}

public function getAvailableGestionnairesIef()
{
    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->whereNull('ief_id')
        ->with('role')
        ->get();
}

public function getGestionnairesByIef(int $iefId)
{
    Ief::findOrFail($iefId);

    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->where('ief_id', $iefId)
        ->with(['ief', 'role'])
        ->get();
}
}
