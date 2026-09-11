<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('enseignants'))
            ->first(fn (array $key) => $key['columns'] === ['lieu_service_id']);

        if ($foreignKey && in_array(strtolower($foreignKey['on_delete']), ['restrict', 'no action'], true)) {
            return;
        }

        Schema::table('enseignants', function (Blueprint $table) use ($foreignKey) {
            if ($foreignKey) {
                $table->dropForeign($foreignKey['name'] ?? ['lieu_service_id']);
            }
            $table->foreign('lieu_service_id')->references('id')->on('lieu_de_services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Conserve la protection des dossiers : ne pas rétablir la suppression en cascade.
    }
};
