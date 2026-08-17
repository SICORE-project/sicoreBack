<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_salary_scales', function (Blueprint $table) {
            $table->id();
            $table->string('engagement_type', 30);
            $table->string('diploma_level', 30)->default('TOUS');
            $table->unsignedTinyInteger('category_level')->default(0);
            $table->decimal('base_salary', 14, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_reference', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(
                ['engagement_type', 'diploma_level', 'category_level', 'effective_from'],
                'payroll_salary_scale_version_unique'
            );
            $table->index(['engagement_type', 'effective_from', 'effective_to']);
        });

        Schema::create('payroll_allowance_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('diploma_level', 30);
            $table->decimal('amount', 14, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_reference', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(
                ['code', 'diploma_level', 'effective_from'],
                'payroll_allowance_rate_version_unique'
            );
        });

        Schema::create('payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('engagement_type', 30);
            $table->string('code', 50);
            $table->decimal('value', 16, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_reference', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(
                ['engagement_type', 'code', 'effective_from'],
                'payroll_parameter_version_unique'
            );
        });

        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('payroll_diploma_level', 30)->nullable()->after('type_engagement');
            $table->unsignedTinyInteger('payroll_category_level')->nullable()->after('payroll_diploma_level');
            $table->decimal('impr_monthly_amount', 14, 2)->nullable()->after('nombre_parts');
            $table->decimal('trimf_monthly_amount', 14, 2)->nullable()->after('impr_monthly_amount');
            $table->decimal('ipm_monthly_amount', 14, 2)->default(0)->after('trimf_monthly_amount');
            $table->decimal('union_checkoff_monthly_amount', 14, 2)->default(0)->after('ipm_monthly_amount');
            $table->timestamp('payroll_profile_configured_at')->nullable()->after('union_checkoff_monthly_amount');
            $table->foreignId('payroll_profile_configured_by')
                ->nullable()
                ->after('payroll_profile_configured_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(
                ['type_engagement', 'payroll_diploma_level', 'payroll_category_level'],
                'enseignants_payroll_profile_index'
            );
        });

        Schema::table('payroll_payslips', function (Blueprint $table) {
            $table->json('profile_snapshot')->nullable()->after('reference');
        });

        $this->seedReferenceRules();
    }

    public function down(): void
    {
        Schema::table('payroll_payslips', function (Blueprint $table) {
            $table->dropColumn('profile_snapshot');
        });

        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropIndex('enseignants_payroll_profile_index');
            $table->dropConstrainedForeignId('payroll_profile_configured_by');
            $table->dropColumn([
                'payroll_diploma_level',
                'payroll_category_level',
                'impr_monthly_amount',
                'trimf_monthly_amount',
                'ipm_monthly_amount',
                'union_checkoff_monthly_amount',
                'payroll_profile_configured_at',
            ]);
        });

        Schema::dropIfExists('payroll_parameters');
        Schema::dropIfExists('payroll_allowance_rates');
        Schema::dropIfExists('payroll_salary_scales');
    }

    private function seedReferenceRules(): void
    {
        $reference = config('payroll_reference');
        $effectiveFrom = $reference['effective_from'];
        $now = now();
        $diplomas = array_keys($reference['diplomas']);

        $salaryRows = [];
        foreach ($reference['contract_salary_grid'] as $category => $amounts) {
            foreach ($diplomas as $index => $diploma) {
                $salaryRows[] = [
                    'engagement_type' => 'contractuel',
                    'diploma_level' => $diploma,
                    'category_level' => $category,
                    'base_salary' => $amounts[$index],
                    'effective_from' => $effectiveFrom,
                    'source_reference' => $reference['salary_grid_source'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        $salaryRows[] = [
            'engagement_type' => 'vacataire',
            'diploma_level' => 'TOUS',
            'category_level' => 0,
            'base_salary' => $reference['vacataire_base_salary'],
            'effective_from' => $effectiveFrom,
            'source_reference' => 'Règle vacataire communiquée le 08/08/2026',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        DB::table('payroll_salary_scales')->insert($salaryRows);

        $allowanceRows = [];
        foreach ($reference['ird_rates'] as $diploma => $amount) {
            $allowanceRows[] = [
                'code' => 'IRD',
                'diploma_level' => $diploma,
                'amount' => $amount,
                'effective_from' => $effectiveFrom,
                'source_reference' => 'Barème IRD communiqué le 08/08/2026',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('payroll_allowance_rates')->insert($allowanceRows);

        $parameterRows = [];
        foreach ($reference['contract_parameters'] as $code => $value) {
            $parameterRows[] = [
                'engagement_type' => 'contractuel',
                'code' => $code,
                'value' => $value,
                'effective_from' => $effectiveFrom,
                'source_reference' => $reference['bulletin_source'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('payroll_parameters')->insert($parameterRows);
    }
};
