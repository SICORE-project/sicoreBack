<?php

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use App\Services\Parametrage\RegionalStructureService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(RegionalStructureService::class);
        Ia::query()->each(fn (Ia $ia) => $service->sync($ia));
        Ief::query()->each(fn (Ief $ief) => $service->sync($ief));
    }

    public function down(): void
    {
        // Conserver les structures auxquelles des utilisateurs peuvent être rattachés.
    }
};
