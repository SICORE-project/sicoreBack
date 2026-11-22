<?php

namespace Tests\Unit;

use App\Http\Requests\PayrollActionRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PayrollTabaskiValidationTest extends TestCase
{
    private int $corpsId;

    private int $iaId;

    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('statuts_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('code'); $table->string('libelle');
        });
        Schema::create('ias', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('annee_academiques', function (Blueprint $table): void {
            $table->id();
        });

        $this->corpsId = DB::table('statuts_enseignant')->insertGetId(['code' => 'VAC', 'libelle' => 'Vacataires']);
        $this->iaId = DB::table('ias')->insertGetId([]);
        $this->academicYearId = DB::table('annee_academiques')->insertGetId([]);
    }

    public function test_advance_requires_ia_and_month_and_rejects_custom_amount(): void
    {
        $valid = $this->validator('apply-tabaski-advance', [
            ...$this->basePayload(),
            'start_month' => '2027-07',
        ]);
        $this->assertTrue($valid->passes());

        $invalid = $this->validator('apply-tabaski-advance', [
            ...$this->basePayload(),
            'ia_ids' => [],
            'start_month' => '2027-13',
            'amount' => 0,
        ]);
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('ia_ids', $invalid->errors()->toArray());
        $this->assertArrayHasKey('start_month', $invalid->errors()->toArray());
        $this->assertArrayHasKey('amount', $invalid->errors()->toArray());
    }

    public function test_deduction_accepts_start_month_but_rejects_manual_month_lists(): void
    {
        $valid = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'amount' => 10000,
        ]);
        $this->assertTrue($valid->passes());

        $nineMonths = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'months' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]);
        $this->assertTrue($nineMonths->fails());

        $duplicate = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'months' => [1, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]);
        $this->assertArrayHasKey('months', $duplicate->errors()->toArray());
    }

    public function test_un_corps_cree_par_le_crud_parametrage_est_accepte(): void
    {
        $corpsId = DB::table('statuts_enseignant')->insertGetId([
            'code' => 'PROFESSEURS-CONTRACTUELS', 'libelle' => 'Professeurs contractuels',
        ]);

        $validation = $this->validator('apply-tabaski-advance', [
            ...$this->basePayload(),
            'statut_enseignant_id' => $corpsId,
            'start_month' => '2027-07',
        ]);

        $this->assertTrue($validation->passes());
    }

    /** @return array<string, mixed> */
    private function basePayload(): array
    {
        return [
            'statut_enseignant_id' => $this->corpsId,
            'ia_ids' => [$this->iaId],
            'start_month' => '2027-01',
            'amount' => 100000,
        ];
    }

    private function validator(string $action, array $payload): \Illuminate\Contracts\Validation\Validator
    {
        $request = PayrollActionRequest::create('/payroll/actions/'.$action, 'POST', $payload);
        $request->setRouteResolver(fn () => new class($action)
        {
            public function __construct(private readonly string $action) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'action' ? $this->action : $default;
            }
        });

        return Validator::make($payload, $request->rules(), $request->messages());
    }
}
