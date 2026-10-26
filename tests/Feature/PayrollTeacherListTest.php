<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Services\PayrollPageService;
use ReflectionMethod;
use Tests\TestCase;

class PayrollTeacherListTest extends TestCase
{
    public function test_registered_teachers_are_listed_without_period_profile_or_calculation(): void
    {
        $teacher = new Enseignant([
            'id' => 42, 'matricule' => 'NEW-001', 'prenom' => 'Nouveau', 'nom' => 'Formateur',
            'salaire_base' => 0, 'payroll_profile_configured_at' => null,
        ]);
        $report = (new ReflectionMethod(PayrollPageService::class, 'payslipReport'))
            ->invoke(app(PayrollPageService::class), null, collect([$teacher]));
        $this->assertCount(1, $report['rows']);
        $this->assertNotContains('Paiement', $report['columns']);
        $row = $report['rows']->first();
        $this->assertSame('NEW-001', $row[2]);
        $this->assertSame('Nouveau Formateur', $row[3]);
        $this->assertSame(['—', '—', '—'], array_slice($row, 4, 3));
        $this->assertCount(count($report['columns']), $row);
        $this->assertFalse($teacher->exists);
        $this->assertNull($teacher->payroll_profile_configured_at);
    }
}
