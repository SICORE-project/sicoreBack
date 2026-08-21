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

        Schema::create('corps_enseignant', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
        });
        Schema::create('ias', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('annee_academiques', function (Blueprint $table): void {
            $table->id();
        });

        $this->corpsId = DB::table('corps_enseignant')->insertGetId(['code' => 'VAC']);
        $this->iaId = DB::table('ias')->insertGetId([]);
        $this->academicYearId = DB::table('annee_academiques')->insertGetId([]);
    }

    public function test_avance_exige_une_ia_un_mois_et_un_montant_positif(): void
    {
        $valid = $this->validator('apply-tabaski-advance', [
            ...$this->basePayload(),
            'month' => 7,
        ]);
        $this->assertTrue($valid->passes());

        $invalid = $this->validator('apply-tabaski-advance', [
            ...$this->basePayload(),
            'ia_ids' => [],
            'month' => 13,
            'amount' => 0,
        ]);
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('ia_ids', $invalid->errors()->toArray());
        $this->assertArrayHasKey('month', $invalid->errors()->toArray());
        $this->assertArrayHasKey('amount', $invalid->errors()->toArray());
    }

    public function test_retenue_exige_exactement_dix_mois_distincts(): void
    {
        $valid = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'months' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        ]);
        $this->assertTrue($valid->passes());

        $nineMonths = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'months' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]);
        $this->assertArrayHasKey('months', $nineMonths->errors()->toArray());

        $duplicate = $this->validator('apply-tabaski-deduction', [
            ...$this->basePayload(),
            'months' => [1, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]);
        $this->assertTrue(collect($duplicate->errors()->keys())->contains(fn (string $key): bool => str_starts_with($key, 'months.')));
    }

    /** @return array<string, mixed> */
    private function basePayload(): array
    {
        return [
            'corps_id' => $this->corpsId,
            'ia_ids' => [$this->iaId],
            'annee_academique_id' => $this->academicYearId,
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
