<?php

namespace App\Http\Controllers\Api\Indemnites\Concerns;

/**
 * Format de réponse JSON standard pour tout le module Indemnites,
 * cohérent avec le style déjà utilisé dans App\Http\Controllers\Api\UserController.
 */
trait ApiResponseTrait
{
    protected function success(string $message, mixed $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
