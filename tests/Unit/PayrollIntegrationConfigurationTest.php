<?php

namespace Tests\Unit;

use JsonException;
use PHPUnit\Framework\TestCase;

class PayrollIntegrationConfigurationTest extends TestCase
{
    public function test_example_environment_targets_the_local_mysql_and_frontend_services(): void
    {
        $environment = file_get_contents(dirname(__DIR__, 2).'/.env.example');

        $this->assertIsString($environment);
        $this->assertStringNotContainsString('<<<<<<<', $environment);
        $this->assertStringNotContainsString('>>>>>>>', $environment);
        $this->assertStringContainsString('APP_URL=http://127.0.0.1:8000', $environment);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $environment);
        $this->assertStringContainsString('DB_DATABASE=sicoreproject_db', $environment);
        $this->assertStringContainsString('FRONTEND_URL=http://127.0.0.1:8001', $environment);
    }

    /** @throws JsonException */
    public function test_composer_lock_is_valid_and_contains_the_payroll_export_dependencies(): void
    {
        $lock = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.lock'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $packages = array_column($lock['packages'] ?? [], 'name');

        $this->assertContains('barryvdh/laravel-dompdf', $packages);
        $this->assertContains('phpoffice/phpword', $packages);
    }
}
