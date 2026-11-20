<?php

namespace Tests\Feature\ActividadSeguridad;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActividadSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $auditorRole;

    private Role $recepcionistaRole;

    private Role $tecnicoRole;

    private Role $consultaRole;

    private User $adminUser;

    private User $auditorUser;

    private User $recepcionistaUser;

    private User $tecnicoUser;

    private User $consultaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $this->auditorRole = Role::where('role_name', 'Auditor')->firstOrFail();
        $this->recepcionistaRole = Role::where('role_name', 'Recepcionista')->firstOrFail();
        $this->tecnicoRole = Role::where('role_name', 'Tecnico de campo')->firstOrFail();
        $this->consultaRole = Role::firstOrCreate(
            ['role_name' => 'Consulta'],
            ['description' => 'Perfil de solo consulta sobre el módulo de clientes.']
        );

        $this->adminUser = $this->createUserWithRole('Administrador Principal', $this->adminRole, 'admin@vergel.com');
        $this->auditorUser = $this->createUserWithRole('Auditor Interno', $this->auditorRole, 'auditor@vergel.com');
        $this->recepcionistaUser = $this->createUserWithRole('Recepcionista Turno', $this->recepcionistaRole, 'recepcion@vergel.com');
        $this->tecnicoUser = $this->createUserWithRole('Técnico Operativo', $this->tecnicoRole, 'tecnico@vergel.com');
        $this->consultaUser = $this->createUserWithRole('Usuario Consulta', $this->consultaRole, 'consulta@vergel.com');
    }

    /**
     * P01 (Acceso sin token): GET a roles/colaboradores sin token. Aserción: assertUnauthorized() (401).
     */
    public function test_p01_acceso_sin_token_a_roles_y_colaboradores_retorna_401(): void
    {
        $this->getJson('/api/v1/roles')->assertUnauthorized();
        $this->getJson('/api/v1/employees')->assertUnauthorized();
    }

    /**
     * P02 (Restricciones): Usuario Recepcionista/Auditor intenta POST/PUT a empleados. Aserción: assertForbidden() (403).
     */
    public function test_p02_restricciones_recepcionista_y_auditor_no_pueden_crear_ni_actualizar_empleados(): void
    {
        $targetEmployee = Employee::create([
            'full_name' => 'Colaborador Existente',
            'role_id' => $this->tecnicoRole->id,
            'phone' => '7000-1111',
            'active' => true,
        ]);

        // Intento con Recepcionista
        Sanctum::actingAs($this->recepcionistaUser);

        $this->postJson('/api/v1/employees', [
            'full_name' => 'Intento Recepcionista',
            'role_id' => $this->tecnicoRole->id,
            'phone' => '7000-2222',
        ])->assertForbidden();

        $this->putJson("/api/v1/employees/{$targetEmployee->id}", [
            'full_name' => 'Modificado por Recepcionista',
        ])->assertForbidden();

        // Intento con Auditor
        Sanctum::actingAs($this->auditorUser);

        $this->postJson('/api/v1/employees', [
            'full_name' => 'Intento Auditor',
            'role_id' => $this->tecnicoRole->id,
            'phone' => '7000-3333',
        ])->assertForbidden();

        $this->putJson("/api/v1/employees/{$targetEmployee->id}", [
            'full_name' => 'Modificado por Auditor',
        ])->assertForbidden();
    }

    /**
     * P03 (Escalamiento): Usuario de nivel bajo intenta PUT inyectando role_id de Admin. Aserción: 403 y base de datos sin cambios en el rol.
     */
    public function test_p03_escalamiento_de_privilegios_inyectando_role_id_admin_retorna_403_y_no_altera_bd(): void
    {
        $lowLevelEmployee = $this->tecnicoUser->employee;
        $originalRoleId = $lowLevelEmployee->role_id;

        Sanctum::actingAs($this->tecnicoUser);

        $response = $this->putJson("/api/v1/employees/{$lowLevelEmployee->id}", [
            'full_name' => $lowLevelEmployee->full_name,
            'role_id' => $this->adminRole->id,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('employees', [
            'id' => $lowLevelEmployee->id,
            'role_id' => $originalRoleId,
        ]);
    }

    /**
     * P04 (Creación de colaborador): Admin crea empleado (POST). Aserción: BD se actualiza, la respuesta JSON omite "password", y un request a login con esos datos da 200 + token.
     */
    public function test_p04_creacion_de_colaborador_por_admin_actualiza_bd_omite_password_y_permite_login(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'full_name' => 'Nuevo Colaborador Vergel',
            'role_id' => $this->recepcionistaRole->id,
            'dui' => '05432198-7',
            'phone' => '7890-1234',
            'email' => 'nuevo.colaborador@vergel.com',
            'password' => 'PasswordSegura123!',
        ];

        $response = $this->postJson('/api/v1/employees', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('employees', [
            'full_name' => 'Nuevo Colaborador Vergel',
            'dui' => '05432198-7',
            'role_id' => $this->recepcionistaRole->id,
        ]);

        $this->assertArrayNotHasKey('password', $response->json('data') ?? []);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'nuevo.colaborador@vergel.com',
            'password' => 'PasswordSegura123!',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ]);
    }

    /**
     * P05 (Validación): Admin envía POST con email duplicado o vacío. Aserción: assertUnprocessable() (422) y conteo de usuarios nuevos = 0.
     */
    public function test_p05_validacion_email_duplicado_o_vacio_retorna_422_y_no_crea_usuarios(): void
    {
        Sanctum::actingAs($this->adminUser);

        $initialUserCount = User::count();

        // Caso 1: Email duplicado (ya existe admin@vergel.com)
        $duplicateEmailResponse = $this->postJson('/api/v1/employees', [
            'full_name' => 'Colaborador Email Duplicado',
            'role_id' => $this->recepcionistaRole->id,
            'email' => 'admin@vergel.com',
            'password' => 'Password123!',
        ]);

        $duplicateEmailResponse->assertUnprocessable();
        $this->assertDatabaseCount('users', $initialUserCount);

        // Caso 2: Email vacío
        $emptyEmailResponse = $this->postJson('/api/v1/employees', [
            'full_name' => 'Colaborador Email Vacío',
            'role_id' => $this->recepcionistaRole->id,
            'email' => '',
            'password' => 'Password123!',
        ]);

        $emptyEmailResponse->assertUnprocessable();
        $this->assertDatabaseCount('users', $initialUserCount);
    }

    /**
     * P06 (Desactivación): Simular Admin cambiando status a inactivo en BD a un usuario válido. Un request subsecuente con el token de ese usuario inactivo debe fallar.
     */
    public function test_p06_desactivacion_de_usuario_en_bd_rechaza_requests_subsecuentes_con_su_token(): void
    {
        $activeEmployee = Employee::create([
            'full_name' => 'Colaborador Temporal',
            'role_id' => $this->recepcionistaRole->id,
            'active' => true,
        ]);

        $validUser = User::factory()->create([
            'employee_id' => $activeEmployee->id,
            'email' => 'temporal@vergel.com',
        ]);

        $token = $validUser->createToken('auth_token')->plainTextToken;

        // Simulamos que el Admin desactiva al colaborador en la BD
        $activeEmployee->update(['active' => false]);

        // Request subsecuente usando el token del usuario inactivo
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customers');

        $response->assertForbidden();
    }

    /**
     * P07 (Manipulación): Request con token Sanctum alterado/falso en los headers. Aserción: API lo rechaza.
     */
    public function test_p07_manipulacion_token_sanctum_alterado_o_falso_es_rechazado(): void
    {
        $realToken = $this->adminUser->createToken('auth_token')->plainTextToken;
        $tamperedToken = $realToken.'_alterado_maliciosamente';

        $this->withHeader('Authorization', 'Bearer '.$tamperedToken)
            ->getJson('/api/v1/employees')
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer 99999|token_totalmente_falso_123456')
            ->getJson('/api/v1/roles')
            ->assertUnauthorized();
    }

    /**
     * P08 (Bitácora): Hacer un POST exitoso y uno fallido. Verificar vía assertDatabaseHas que el modelo de bitácora registre al usuario, la acción y no guarde contraseñas.
     */
    public function test_p08_bitacora_registra_post_exitoso_y_fallido_con_usuario_accion_y_sin_passwords(): void
    {
        // 1. POST exitoso (Admin crea un empleado)
        Sanctum::actingAs($this->adminUser);

        $this->postJson('/api/v1/employees', [
            'full_name' => 'Colaborador Auditado',
            'role_id' => $this->tecnicoRole->id,
            'phone' => '7123-4567',
        ])->assertCreated();

        $createdEmployee = Employee::where('full_name', 'Colaborador Auditado')->firstOrFail();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->adminUser->employee_id,
            'affected_table' => 'employees',
            'affected_record_id' => $createdEmployee->id,
            'action' => 'insert',
        ]);

        // 2. POST fallido por permisos (Recepcionista intenta crear un empleado enviando además una contraseña)
        Sanctum::actingAs($this->recepcionistaUser);

        $this->postJson('/api/v1/employees', [
            'full_name' => 'Intento No Autorizado',
            'role_id' => $this->adminRole->id,
            'password' => 'ClaveSecretaNoGuardar123!',
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->recepcionistaUser->employee_id,
            'affected_table' => 'employees',
            'action' => 'denied',
        ]);

        // Verificar que ningún registro de bitácora almacene contraseñas
        $logs = AuditLog::all();
        foreach ($logs as $log) {
            $newDataJson = json_encode($log->new_data ?? []);
            $oldDataJson = json_encode($log->old_data ?? []);

            $this->assertArrayNotHasKey('password', $log->new_data ?? []);
            $this->assertArrayNotHasKey('password', $log->old_data ?? []);
            $this->assertStringNotContainsString('ClaveSecretaNoGuardar123!', $newDataJson);
            $this->assertStringNotContainsString('ClaveSecretaNoGuardar123!', $oldDataJson);
        }
    }

    /**
     * P09 (Permisos Clientes): Admin -> GET y POST exitosos (200/201). Consulta -> GET exitoso (200), pero POST falla (403).
     */
    public function test_p09_permisos_clientes_admin_get_y_post_exitosos_consulta_get_200_y_post_403(): void
    {
        // Admin: GET (200) y POST (201) exitosos
        Sanctum::actingAs($this->adminUser);

        $this->getJson('/api/v1/customers')
            ->assertOk();

        $this->postJson('/api/v1/customers', [
            'legal_name' => 'Jardines El Salvador S.A. de C.V.',
            'address' => 'Colonia Escalón, San Salvador',
            'municipality' => 'San Salvador',
            'phone' => '2200-1100',
        ])->assertCreated();

        // Consulta: GET exitoso (200), pero POST falla (403)
        Sanctum::actingAs($this->consultaUser);

        $this->getJson('/api/v1/customers')
            ->assertOk();

        $this->postJson('/api/v1/customers', [
            'legal_name' => 'Cliente No Autorizado S.A.',
            'address' => 'Santa Tecla, La Libertad',
            'municipality' => 'Santa Tecla',
            'phone' => '2200-2200',
        ])->assertForbidden();
    }

    private function createUserWithRole(string $fullName, Role $role, string $email, bool $active = true): User
    {
        $employee = Employee::create([
            'full_name' => $fullName,
            'role_id' => $role->id,
            'active' => $active,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
            'email' => $email,
        ]);
    }
}
