<?php

namespace Tests\Feature\Roles;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_four_predefined_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 4);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Administrador',
            'description' => 'Control total del sistema y gestión integral de la plataforma.',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Auditor',
            'description' => 'Supervisión de operaciones, consulta de reportes y trazabilidad en bitácoras de auditoría.',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Recepcionista',
            'description' => 'Atención al cliente, gestión de citas e ingreso de solicitudes y cotizaciones.',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_name' => 'Tecnico de campo',
            'description' => 'Nivel operativo en terreno, ejecución de servicios de jardinería y actualización de citas asignadas.',
        ]);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 4);
    }
}
