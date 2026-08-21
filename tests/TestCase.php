<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
        // On retire la ligne "use CreatesApplication;" si elle n'existe pas dans votre projet Laravel standard

    /**
     * S'exécute automatiquement avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Désactive les contraintes MySQL pour permettre à RefreshDatabase de vider 'users'
        Schema::disableForeignKeyConstraints();
    }

    /**
     * S'exécute automatiquement après chaque test
     */
    protected function tearDown(): void
    {
        // Réactive proprement les contraintes pour la suite
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }
}
