<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Models\Paie\AnneeAcademique;
use App\Services\Parametrage\AnneeAcademiqueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnneeAcademiqueDeletionTest extends TestCase
{
    private function year(string $libelle, string $start, string $end, bool $active = false): AnneeAcademique
    {
        $id = DB::table('annee_academiques')->insertGetId([
            'libelle' => $libelle, 'date_debut' => $start, 'date_fin' => $end, 'est_active' => $active,
        ]);

        return AnneeAcademique::findOrFail($id);
    }

    public function test_only_unused_inactive_year_is_deleted(): void
    {
        Schema::create('annee_academiques', function ($table) {
            $table->id();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('est_active')->default(false);
            $table->timestamps();
        });
        Schema::create('payroll_periods', function ($table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
        Schema::create('payroll_elements', function ($table) {
            $table->id();
            $table->unsignedBigInteger('annee_academique_id')->nullable();
        });
        DB::table('payroll_periods')->insert(['start_date' => '2026-11-01', 'end_date' => '2026-11-30']);
        $controller = new AnneeAcademiqueController(new AnneeAcademiqueService);

        $unused = $this->year('Sans paie', '2030-10-01', '2031-07-31');
        $this->assertSame(200, $controller->destroy($unused->id)->getStatusCode());
        $this->assertDatabaseMissing('annee_academiques', ['id' => $unused->id]);

        $withPeriod = $this->year('Avec période', '2026-10-01', '2027-07-31');
        $this->assertSame(409, $controller->destroy($withPeriod->id)->getStatusCode());
        $this->assertDatabaseHas('annee_academiques', ['id' => $withPeriod->id]);

        $withElement = $this->year('Avec élément', '2032-10-01', '2033-07-31');
        DB::table('payroll_elements')->insert(['annee_academique_id' => $withElement->id]);
        $this->assertSame(409, $controller->destroy($withElement->id)->getStatusCode());
        $this->assertDatabaseHas('annee_academiques', ['id' => $withElement->id]);

        $active = $this->year('Active', '2034-10-01', '2035-07-31', true);
        $this->assertSame(409, $controller->destroy($active->id)->getStatusCode());
        $this->assertDatabaseHas('annee_academiques', ['id' => $active->id]);
    }
}
