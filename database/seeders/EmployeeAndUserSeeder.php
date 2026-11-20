<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $adminRole = Role::firstOrCreate(['role_name' => 'Administrador']);
        $auditorRole = Role::firstOrCreate(['role_name' => 'Auditor']);
        $recepRole = Role::firstOrCreate(['role_name' => 'Recepcionista']);
        $tecnicoRole = Role::firstOrCreate(['role_name' => 'Tecnico de campo']);
        $colabRole = Role::firstOrCreate(['role_name' => 'Colaborador'], ['description' => 'Personal operativo']);

        $staff = [
            // Técnicos de campo (5 técnicos)
            ['name' => 'Jorge Ramos', 'role' => $tecnicoRole, 'email' => 'tecnico@vergel.com', 'dui' => '02456789-1', 'phone' => '7123-4501', 'muni' => 'Santa Tecla'],
            ['name' => 'Miguel Ángel Guardado', 'role' => $tecnicoRole, 'email' => 'miguel.guardado@vergel.com', 'dui' => '03124567-2', 'phone' => '7123-4502', 'muni' => 'Antiguo Cuscatlán'],
            ['name' => 'Roberto Carlos Beltrán', 'role' => $tecnicoRole, 'email' => 'roberto.beltran@vergel.com', 'dui' => '04567891-3', 'phone' => '7123-4503', 'muni' => 'San Salvador'],
            ['name' => 'Eduardo José Portillo', 'role' => $tecnicoRole, 'email' => 'eduardo.portillo@vergel.com', 'dui' => '01987654-4', 'phone' => '7123-4504', 'muni' => 'Soyapango'],
            ['name' => 'José Manuel Alas', 'role' => $tecnicoRole, 'email' => 'jose.alas@vergel.com', 'dui' => '05234567-5', 'phone' => '7123-4505', 'muni' => 'Apopa'],

            // Recepción y Servicio al Cliente (3 recepcionistas)
            ['name' => 'Sofía Martínez', 'role' => $recepRole, 'email' => 'recepcion@vergel.com', 'dui' => '03678912-6', 'phone' => '7234-5601', 'muni' => 'San Salvador'],
            ['name' => 'Valeria Beatriz Cruz', 'role' => $recepRole, 'email' => 'valeria.cruz@vergel.com', 'dui' => '04123456-7', 'phone' => '7234-5602', 'muni' => 'Santa Tecla'],
            ['name' => 'Andrea Michelle Rivas', 'role' => $recepRole, 'email' => 'andrea.rivas@vergel.com', 'dui' => '05678901-8', 'phone' => '7234-5603', 'muni' => 'Mejicanos'],

            // Auditoría y Calidad (2 auditores)
            ['name' => 'Lucía Gómez', 'role' => $auditorRole, 'email' => 'auditor@vergel.com', 'dui' => '01234567-9', 'phone' => '7345-6701', 'muni' => 'San Salvador'],
            ['name' => 'Fernando Enrique Melgar', 'role' => $auditorRole, 'email' => 'fernando.melgar@vergel.com', 'dui' => '02890123-0', 'phone' => '7345-6702', 'muni' => 'Antiguo Cuscatlán'],

            // Administradores (2 admins)
            ['name' => 'Carlos Mendoza', 'role' => $adminRole, 'email' => 'admin@vergel.com', 'dui' => '01456789-1', 'phone' => '7456-7801', 'muni' => 'San Salvador'],
            ['name' => 'Gabriela Elena Solís', 'role' => $adminRole, 'email' => 'gabriela.solis@vergel.com', 'dui' => '03890123-2', 'phone' => '7456-7802', 'muni' => 'Santa Tecla'],

            // Colaboradores operativos
            ['name' => 'Manuel Antonio Palacios', 'role' => $colabRole, 'email' => 'manuel.palacios@vergel.com', 'dui' => '04789012-3', 'phone' => '7567-8901', 'muni' => 'San Salvador'],
            ['name' => 'Mauricio Alexander Peña', 'role' => $colabRole, 'email' => 'mauricio.pena@vergel.com', 'dui' => '05890123-4', 'phone' => '7567-8902', 'muni' => 'Ilopango'],
            ['name' => 'Christian David Orellana', 'role' => $colabRole, 'email' => 'christian.orellana@vergel.com', 'dui' => '06123456-5', 'phone' => '7567-8903', 'muni' => 'San Marcos'],
        ];

        foreach ($staff as $person) {
            $emp = Employee::firstOrCreate(
                ['dui' => $person['dui']],
                [
                    'full_name' => $person['name'],
                    'role_id' => $person['role']->id,
                    'phone' => $person['phone'],
                    'address' => 'Residencial Los Almendros, Polígono D #12',
                    'municipality' => $person['muni'],
                    'department' => 'San Salvador',
                    'hire_date' => '2025-01-15',
                    'birth_date' => '1992-06-20',
                    'active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => $person['email']],
                [
                    'password' => $password,
                    'employee_id' => $emp->id,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
