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
        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();

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

        // 2. Usuario Contador
        $contadorRole = Role::where('role_name', 'Contador')->firstOrFail();

        $contadorEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Lucía Gómez (Contabilidad)',
                'role_id' => $contadorRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'contador@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $contadorEmployee->id,
                'email_verified_at' => now(),
            ]
        );

        // 3. Usuario Colaborador de Campo
        $colaboradorRole = Role::where('role_name', 'Colaborador')->firstOrFail();

        $colaboradorEmployee = Employee::firstOrCreate(
            [
                'full_name' => 'Jorge Ramos (Operativo de Campo)',
                'role_id' => $colaboradorRole->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'colaborador@vergel.com'],
            [
                'password' => Hash::make('password123'),
                'employee_id' => $colaboradorEmployee->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
