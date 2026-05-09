<?php

namespace Tests;

use Database\Seeders\DevisesTestSeeder;
use Database\Seeders\ModelHasRolesTestSeeder;
use Database\Seeders\PaysDeviseTestSeeder;
use Database\Seeders\PaysTestSeeder;
use Database\Seeders\PermissionsTestSeeder;
use Database\Seeders\RoleHasPermissionsTestSeeder;
use Database\Seeders\RolesTestSeeder;
use Database\Seeders\TFeatureTestSeeder;
use Database\Seeders\TModuleTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class BaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaysTestSeeder::class);
        $this->seed(DevisesTestSeeder::class);
        $this->seed(PaysDeviseTestSeeder::class);
        $this->seed(TModuleTestSeeder::class);
        $this->seed(TFeatureTestSeeder::class);
        $this->seed(RolesTestSeeder::class);
        $this->seed(PermissionsTestSeeder::class);
        $this->seed(RoleHasPermissionsTestSeeder::class);
        $this->seed(ModelHasRolesTestSeeder::class);
    }
}