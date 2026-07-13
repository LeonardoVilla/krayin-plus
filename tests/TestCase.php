<?php

namespace Tests;

use Database\Seeders\AccessProfilesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as KrayinDatabaseSeeder;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Migra o banco de teste do zero e roda os seeders essenciais
     * (papeis/dados padrao do Krayin + matriz de perfis de acesso) antes
     * de cada teste. RefreshDatabase cuida de rodar isso só quando o
     * schema muda, e envolve cada teste numa transação que é desfeita no
     * final — então testes não vazam dado um pro outro.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KrayinDatabaseSeeder::class);
        $this->seed(AccessProfilesSeeder::class);
    }
}
