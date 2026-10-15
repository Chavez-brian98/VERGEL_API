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
                'role_name' => 'Admin',
                'description' => 'Control total del sistema.',
            ],
            [
                'role_name' => 'Administrador Financiero / Contador',
                'description' => 'Acceso a la billetera, reportes, facturación y modificación de movimientos financieros.',
            ],
            [
                'role_name' => 'Colaborador (Personal de campo)',
                'description' => 'Nivel operativo, encargado de ver su agenda y marcar los servicios de jardinería como completados.',
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
