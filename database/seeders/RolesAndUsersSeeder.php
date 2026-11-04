<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Auditor',
                'description' => 'Auditor con acceso a revisión y seguimiento de actividades',
                'full_name' => 'Auditor Vergel',
                'email' => 'auditor@vergel.com',
            ],
            [
                'role_name' => 'Recepcionista',
                'description' => 'Recepcionista encargado de atención y registro de clientes',
                'full_name' => 'Recepcionista Vergel',
                'email' => 'recepcionista@vergel.com',
            ],
            [
                'role_name' => 'Tecnico de campo',
                'description' => 'Técnico de campo encargado de servicios y visitas',
                'full_name' => 'Técnico de Campo Vergel',
                'email' => 'tecnico@vergel.com',
            ],
        ];

        $password = Hash::make('password123');

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['role_name' => $roleData['role_name']],
                ['description' => $roleData['description']]
            );

            $employee = Employee::firstOrCreate(
                [
                    'full_name' => $roleData['full_name'],
                    'role_id' => $role->id,
                ],
                [
                    'active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => $roleData['email']],
                [
                    'password' => $password,
                    'employee_id' => $employee->id,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
