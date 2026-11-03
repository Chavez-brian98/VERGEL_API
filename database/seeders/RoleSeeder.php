<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Administrador',
                'description' => 'Control total del sistema y gestión integral de la plataforma.',
            ],
            [
                'role_name' => 'Auditor',
                'description' => 'Supervisión de operaciones, consulta de reportes y trazabilidad en bitácoras de auditoría.',
            ],
            [
                'role_name' => 'Recepcionista',
                'description' => 'Atención al cliente, gestión de citas e ingreso de solicitudes y cotizaciones.',
            ],
            [
                'role_name' => 'Tecnico de campo',
                'description' => 'Nivel operativo en terreno, ejecución de servicios de jardinería y actualización de citas asignadas.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['role_name' => $role['role_name']],
                ['description' => $role['description']]
            );
        }
    }
}
