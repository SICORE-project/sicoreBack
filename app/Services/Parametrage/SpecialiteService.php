<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Specialite;

class SpecialiteService
{
    public function create(array $data): Specialite
    {
        $data['est_actif'] = $data['est_actif'] ?? true;

        return Specialite::create($data);
    }
}