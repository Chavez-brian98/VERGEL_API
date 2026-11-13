<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Usuario Administrador
        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();

        $adminEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Carlos Mendoza (Administración)',
                'role_id' => $adminRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $adminEmployee->id,
                'email_verified_at' => now(),
            ]
        );

        // 2. Usuario Auditor
        $auditorRole = Role::where('role_name', 'Auditor')->firstOrFail();

        $auditorEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Lucía Gómez (Auditoría)',
                'role_id' => $auditorRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'auditor@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $auditorEmployee->id,
                'email_verified_at' => now(),
            ]
        );

        // 3. Usuario Recepcionista
        $recepcionistaRole = Role::where('role_name', 'Recepcionista')->firstOrFail();

        $recepcionistaEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Sofía Martínez (Recepción)',
                'role_id' => $recepcionistaRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'recepcion@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $recepcionistaEmployee->id,
                'email_verified_at' => now(),
            ]
        );

        // 4. Usuario Técnico de Campo
        $tecnicoRole = Role::where('role_name', 'Tecnico de campo')->firstOrFail();

        $tecnicoEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Jorge Ramos (Técnico de Campo)',
                'role_id' => $tecnicoRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'tecnico@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $tecnicoEmployee->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
