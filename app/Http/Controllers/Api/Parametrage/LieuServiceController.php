<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\LieuService;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Region;
use App\Services\Administration\OrganizationalScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LieuServiceController extends Controller
{
    public function iaOptions()
    {
        $ias = Ia::actif()
            ->with(['lieuxServices' => fn ($query) => $query->actif()->where('type', 'IA')->orderBy('id')])
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle'])
            ->map(fn (Ia $ia) => [
                'id' => $ia->id,
                'code' => $ia->code,
                'libelle' => $ia->libelle,
                'lieu_service_id' => $ia->lieuxServices->first()?->id,
            ])
            ->filter(fn (array $ia) => $ia['lieu_service_id'] !== null)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $ias,
        ]);
    }

    public function manage()
    {
        return response()->json([
            'success' => true,
            'data' => LieuService::with(['ia:id,code,libelle', 'ief:id,ia_id,code,libelle'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
        ]);
    }
    public function store(Request $request)
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'libelle' => trim((string) $request->input('libelle')),
            'type' => $request->input('type', 'IEF'),
            'perimetre' => $request->input('perimetre', 'regional'),
            'est_actif' => $request->input('est_actif', true),
        ]);
        $lieu = LieuService::create($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Lieu de service créé avec succès.', 'data' => $this->formatLieu($lieu)], 201);
    }

    public function show(LieuService $lieuService)
    {
        return response()->json(['success' => true, 'data' => $this->formatLieu($lieuService)]);
    }

    public function update(Request $request, LieuService $lieuService)
    {
        $lieuService->update($this->validated($request, $lieuService));

        return response()->json(['success' => true, 'message' => 'Lieu de service mis à jour avec succès.', 'data' => $this->formatLieu($lieuService->fresh())]);
    }

    public function destroy(LieuService $lieuService)
    {
        if ($lieuService->users()->exists()) {
            return response()->json(['success' => false, 'message' => 'Ce lieu de service est lié à des utilisateurs et ne peut pas être supprimé.'], 409);
        }

        $lieuService->delete();

        return response()->json(['success' => true, 'message' => 'Lieu de service supprimé avec succès.']);
    }
    public function index(Request $request, OrganizationalScope $scope)
    {
        $lieux = $this->visibleLieux($request, $scope);

        return response()->json([
            'success' => true,
            'data' => $lieux->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
            'perimetres' => [
                'national' => $lieux->filter(fn (LieuService $lieu) => $this->normalizedPerimetre($lieu) === 'national')
                    ->map(fn (LieuService $lieu) => $this->formatLieu($lieu))->values(),
                'regional' => $this->regionalHierarchy($lieux),
            ],
        ]);
    }

    public function national(Request $request, OrganizationalScope $scope)
    {
        $data = $this->visibleLieux($request, $scope)
            ->filter(fn (LieuService $lieu) => $this->normalizedPerimetre($lieu) === 'national')
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
            ->filter(fn (LieuService $lieu) => $this->normalizedPerimetre($lieu) === 'regional')
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
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function regionalHierarchy(Collection $lieux): Collection
    {
        $regions = Region::query()->get(['id', 'code', 'nom']);

        return $lieux->filter(fn (LieuService $lieu) => $this->normalizedPerimetre($lieu) === 'regional')
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
            'perimetre' => $lieu->perimetre ?? $this->inferPerimetreFromType($lieu->type),
            'ia_id' => $lieu->ia_id,
            'ief_id' => $lieu->ief_id,
            'est_actif' => $lieu->est_actif,
        ];
    }

    private function inferPerimetreFromType(?string $type): string
    {
        if (in_array(strtoupper((string) $type), ['DRH', 'DAGE', 'DECPC'], true)) {
            return 'national';
        }

        return 'regional';
    }

    private function normalizedPerimetre(LieuService $lieu): string
    {
        return $lieu->perimetre ?? $this->inferPerimetreFromType($lieu->type);
    }

    private function validated(Request $request, ?LieuService $lieuService = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('lieu_de_services', 'code')->ignore($lieuService)],
            'libelle' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['DRH', 'DAGE', 'DECPC', 'IA', 'IEF'])],
            'perimetre' => ['required', Rule::in(['national', 'regional'])],
            'ia_id' => ['nullable', 'integer', Rule::requiredIf(fn () => in_array($request->input('type'), ['IA', 'IEF'], true)), Rule::exists('ias', 'id')],
            'ief_id' => ['nullable', 'integer', Rule::requiredIf(fn () => $request->input('type') === 'IEF'), Rule::exists('iefs', 'id')->where(fn ($query) => $query->where('ia_id', $request->input('ia_id')))],
            'est_actif' => ['required', 'boolean'],
        ]);

        $data['perimetre'] = in_array($data['type'], ['DRH', 'DAGE', 'DECPC'], true) ? 'national' : 'regional';

        if ($data['perimetre'] === 'national') {
            $data['ia_id'] = null;
            $data['ief_id'] = null;
        }

        return $data;
    }
}
