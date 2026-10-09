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
        $role = Role::firstOrCreate(
            ['role_name' => 'Administrador'],
            ['description' => 'Colaborador administrativo con acceso total al sistema']
        );

        $employee = Employee::firstOrCreate(
            [
                'full_name' => 'Carlos Mendoza (Administración)',
                'role_id' => $role->id,
            ],
            [
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@vergel.com'],
            [
                'name' => 'Administrador Vergel',
                'password' => Hash::make('password123'),
                'employee_id' => $employee->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
