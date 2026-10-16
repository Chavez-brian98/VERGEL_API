<?php

namespace Tests\Feature\Roles;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_three_predefined_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 3);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Admin',
            'description' => 'Control total del sistema.',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Contador',
            'description' => 'Acceso a la billetera, reportes, facturación y modificación de movimientos financieros.',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Colaborador',
            'description' => 'Nivel operativo, encargado de ver su agenda y marcar los servicios de jardinería como completados.',
        ]);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 3);
    }
}
