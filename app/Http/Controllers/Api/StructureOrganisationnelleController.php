<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\LieuService;

class StructureOrganisationnelleController extends Controller
{
    public function index()
    {
        $lieux = LieuService::query()
            ->actif()
            ->with(['ia:id,code,libelle', 'ief:id,ia_id,code,libelle'])
            ->orderBy('perimetre')
            ->orderBy('type')
            ->orderBy('libelle')
            ->get();

        $format = static fn (LieuService $lieu): array => [
            'id' => $lieu->id,
            'code' => $lieu->code,
            'libelle' => $lieu->libelle,
            'type' => $lieu->type,
            'perimetre' => $lieu->perimetre,
            'ia_id' => $lieu->ia_id,
            'ief_id' => $lieu->ief_id,
        ];

        $regional = $lieux->where('perimetre', 'regional')
            ->groupBy('ia_id')
            ->map(function ($services) use ($format) {
                $ia = $services->first()->ia;

                return [
                    'ia' => $ia ? [
                        'id' => $ia->id,
                        'code' => $ia->code,
                        'libelle' => $ia->libelle,
                    ] : null,
                    'iefs' => $services->where('type', 'IEF')->values()->map($format),
                ];
            })->values();

        return response()->json([
            'success' => true,
            // Liste plate conservée pour les clients existants.
            'data' => $lieux->map($format)->values(),
            'perimetres' => [
                'national' => $lieux->where('perimetre', 'national')->values()->map($format),
                'regional' => $regional,
            ],
        ]);
    }
}