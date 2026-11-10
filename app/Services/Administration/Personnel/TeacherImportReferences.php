<?php

namespace App\Services\Administration\Personnel;

use App\Models\Admin\User;
use App\Services\Administration\IaScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherImportReferences
{
    public const FIELDS = [
        'statut_enseignant' => ['statut_enseignant_id', 'statuts_enseignant'],
        'corps' => ['corps_id', 'corps_enseignant'],
        'ief' => ['ief_id', 'iefs'],
        'categorie' => ['categorie_id', 'categories'],
        'diplome' => ['diplome_id', 'diplomes'],
        'lieu_service' => ['lieu_service_id', 'lieu_de_services'],
    ];

    private array $catalogues = [];

    public function resolve(array $data, User $user): array
    {
        foreach (self::FIELDS as $label => [$field, $table]) {
            if (! empty($data[$label])) {
                $candidates = $this->catalogue($table)->filter(fn ($row) => $this->key($row->libelle) === $this->key($data[$label]));
                if ($label === 'ief' && $user->hasRole('gestionnaire_ia')) {
                    $ia = app(IaScope::class)->id($user);
                    $candidates = $candidates->where('ia_id', $ia);
                } elseif ($label === 'ief' && ! empty($data['ia_id'])) {
                    $candidates = $candidates->where('ia_id', $data['ia_id']);
                }
                if ($label === 'categorie') {
                    $status = $this->catalogue('statuts_enseignant')->firstWhere('id', $data['statut_enseignant_id'] ?? null);
                    $contract = $status && ($this->key($status->libelle) === 'contractuel' || $this->key($status->code ?? '') === 'contractuel');
                    $corps = $contract
                        ? $this->catalogue('corps_enseignant')->first(fn ($row) => $this->key($row->code ?? '') === 'pc')?->id
                        : ($data['corps_id'] ?? null);
                    if ($corps) $candidates = $candidates->where('corps_id', $corps);
                }
                if ($label === 'diplome' && ! empty($data['categorie_id'])) $candidates = $candidates->where('categorie_id', $data['categorie_id']);
                if ($label === 'lieu_service' && ! empty($data['ief_id'])) $candidates = $candidates->where('ief_id', $data['ief_id']);
                if ($candidates->count() !== 1) {
                    $reason = $candidates->isEmpty() ? 'introuvable dans le référentiel ou le périmètre autorisé' : 'ambigu : plusieurs correspondances';
                    throw ValidationException::withMessages([$label => 'Le libellé « '.$data[$label].' » est '.$reason.'.']);
                }
                $id = $candidates->first()->id;
                if (! empty($data[$field]) && (int) $data[$field] !== (int) $id) {
                    throw ValidationException::withMessages([$label => 'Le libellé et l’identifiant fournis ne correspondent pas.']);
                }
                $data[$field] = $id;
            }
            unset($data[$label]);
        }
        if (! array_key_exists('ia_id', $data)) {
            $data['ia_id'] = $this->catalogue('iefs')->firstWhere('id', $data['ief_id'] ?? null)?->ia_id;
        }
        return $data;
    }

    private function catalogue(string $table)
    {
        if (! isset($this->catalogues[$table])) {
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'deleted_at')) $query->whereNull('deleted_at');
            $this->catalogues[$table] = $query->get();
        }
        return $this->catalogues[$table];
    }

    private function key(string $value): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/u', ' ', trim($value))));
    }
}
