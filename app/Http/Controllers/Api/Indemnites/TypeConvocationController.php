<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\TypeConvocation;

/**
 * Endpoint minimal (liste seule) pour alimenter le <select> "Type de
 * convocation" du formulaire front (resources/views/.../convocations/create.blade.php).
 
 */
class TypeConvocationController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $types = TypeConvocation::query()
            ->where('est_actif', true)
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle']);

        return $this->success('Types de convocation.', $types);
    }
}
