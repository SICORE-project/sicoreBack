<?php

namespace App\Rules;

use App\Models\Admin\Role;
use App\Models\Administration\StructureOrganisationnelle;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompatibleRoleStructure implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $relatedId,
        private readonly bool $valueIsRole = false,
    ) {}

    public static function structureForRole(int|string|null $roleId): self
    {
        return new self($roleId);
    }

    public static function roleForStructure(int|string|null $structureId): self
    {
        return new self($structureId, true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $roleId = $this->valueIsRole ? $value : $this->relatedId;
        $structureId = $this->valueIsRole ? $this->relatedId : $value;

        if ($roleId === null || $structureId === null || $structureId === '') {
            return;
        }

        $role = Role::query()->find($roleId);
        $structure = StructureOrganisationnelle::query()->find($structureId);

        if (! $role || ! $structure) {
            return;
        }

        $allowedTypes = config("role_structure.allowed_structure_types.{$role->niveau}");

        if ($allowedTypes !== null && ! in_array(strtoupper($structure->type), $allowedTypes, true)) {
            $fail("Le role {$role->niveau} n'est pas autorise pour une structure de type {$structure->type}.");
        }
    }
}
