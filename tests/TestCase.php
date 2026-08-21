<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * S'exécute automatiquement avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Désactive les contraintes MySQL pour permettre à RefreshDatabase de vider 'users'
        Schema::disableForeignKeyConstraints();
    }

    /**
     * S'exécute automatiquement après chaque test
     */
    protected function tearDown(): void
    {
        // 2. Réactive proprement les contraintes pour ne pas fausser vos requêtes d'application
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }
}
