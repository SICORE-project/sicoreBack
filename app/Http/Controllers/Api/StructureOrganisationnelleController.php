<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\LieuService;
use App\Models\Parametrage\Region;
use App\Services\Administration\OrganizationalScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StructureOrganisationnelleController extends Controller
{
    public function index(Request $request, OrganizationalScope $scope)
    {
        $lieux = $this->visibleLieux($request, $scope);

        return response()->json([
            'success' => true,
            'data' => $lieux->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
            'perimetres' => [
                'national' => $lieux->where('perimetre', 'national')
                    ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
                'regional' => $this->regionalHierarchy($lieux),
            ],
        ]);
    }

    public function national(Request $request, OrganizationalScope $scope)
    {
        $data = $this->visibleLieux($request, $scope)
            ->where('perimetre', 'national')
            ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function regions(Request $request, OrganizationalScope $scope)
    {
        $data = $this->regionalHierarchy($this->visibleLieux($request, $scope))
            ->map(fn (array $item) => $item['region'])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function ias(Request $request, string $region, OrganizationalScope $scope)
    {
        $group = $this->regionalHierarchy($this->visibleLieux($request, $scope))
            ->first(fn (array $item) => (string) $item['region']['id'] === $region
                || $item['region']['code'] === $region);

        return response()->json(['success' => true, 'data' => $group['ias'] ?? []]);
    }

    public function iefs(Request $request, int $ia, OrganizationalScope $scope)
    {
        $data = $this->visibleLieux($request, $scope)
            ->where('perimetre', 'regional')
            ->where('type', 'IEF')
            ->where('ia_id', $ia)
            ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function visibleLieux(Request $request, OrganizationalScope $scope): Collection
    {
        $query = LieuService::query();
        $scope->apply($query, $request->user());

        return $query->actif()
            ->with(['ia:id,code,libelle,region_id', 'ief:id,ia_id,code,libelle'])
            ->orderBy('perimetre')->orderBy('type')->orderBy('libelle')
            ->get();
    }

    private function regionalHierarchy(Collection $lieux): Collection
    {
        $regions = Region::query()->get(['id', 'code', 'nom']);

        return $lieux->where('perimetre', 'regional')
            ->whereNotNull('ia_id')
            ->groupBy('ia_id')
            ->map(function (Collection $services) use ($regions): array {
                $ia = $services->first()->ia;
                $regionReference = (string) ($ia?->region_id ?? '');
                $region = $regions->first(fn (Region $item) =>
                    (string) $item->id === $regionReference || $item->code === $regionReference
                );
                $iaService = $services->firstWhere('type', 'IA');

                return [
                    'region_key' => $region ? (string) $region->id : 'sans-region-'.$regionReference,
                    'region' => [
                        'id' => $region?->id ?? $regionReference,
                        'code' => $region?->code ?? $regionReference,
                        'libelle' => $region?->nom ?? $regionReference,
                    ],
                    'ia' => [
                        'id' => $ia?->id ?? $services->first()->ia_id,
                        'lieu_service_id' => $iaService?->id,
                        'code' => $ia?->code ?? $iaService?->code,
                        'libelle' => $ia?->libelle ?? $iaService?->libelle,
                        'iefs' => $services->where('type', 'IEF')
                            ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
                    ],
                ];
            })
            ->groupBy('region_key')
            ->map(fn (Collection $items): array => [
                'region' => $items->first()['region'],
                'ias' => $items->pluck('ia')->sortBy('libelle')->values(),
            ])
            ->sortBy('region.libelle')
            ->values();
    }

    private function formatLieu(LieuService $lieu): array
    {
        return [
            'id' => $lieu->id,
            'code' => $lieu->code,
            'libelle' => $lieu->libelle,
            'type' => $lieu->type,
            'perimetre' => $lieu->perimetre,
            'ia_id' => $lieu->ia_id,
            'ief_id' => $lieu->ief_id,
        ];
    }
}