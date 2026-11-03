<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $receptionistUser;

    private User $auditorUser;

    private Employee $technician;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $receptionistRole = Role::where('role_name', 'Recepcionista')->firstOrFail();
        $auditorRole = Role::where('role_name', 'Auditor')->firstOrFail();
        $techRole = Role::where('role_name', 'Tecnico de campo')->firstOrFail();

        $adminEmp = Employee::create([
            'full_name' => 'Admin Usuario',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);
        $this->adminUser = User::factory()->create(['employee_id' => $adminEmp->id]);

        $recepEmp = Employee::create([
            'full_name' => 'Recepcionista Usuario',
            'role_id' => $receptionistRole->id,
            'active' => true,
        ]);
        $this->receptionistUser = User::factory()->create(['employee_id' => $recepEmp->id]);

        $auditEmp = Employee::create([
            'full_name' => 'Auditor Usuario',
            'role_id' => $auditorRole->id,
            'active' => true,
        ]);
        $this->auditorUser = User::factory()->create(['employee_id' => $auditEmp->id]);

        $this->technician = Employee::create([
            'full_name' => 'Técnico de Campo Asignado',
            'role_id' => $techRole->id,
            'active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Poda de Formación y Césped',
            'price' => 50.00,
            'unit_of_measure' => 'm2',
        ]);
    }

    public function test_admin_can_schedule_appointment_with_existing_customer(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Mario Bros Residencial',
            'phone' => '7000-1111',
            'address' => 'Avenida El Espino #45',
            'municipality' => 'Antiguo Cuscatlán',
            'department' => 'La Libertad',
            'status' => 'active',
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
            'status' => 'confirmed',
            'notes' => 'Cita manual agendada por administración',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.customerId', $customer->id)
            ->assertJsonPath('data.employeeId', $this->technician->id)
            ->assertJsonPath('data.serviceId', $this->service->id)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.origin', 'manual')
            ->assertJsonPath('data.quoteId', null)
            ->assertJsonPath('data.notes', 'Cita manual agendada por administración');

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'service_id' => $this->service->id,
            'status' => 'confirmed',
            'origin' => 'manual',
            'quote_id' => null,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'appointments',
            'action' => 'insert',
        ]);
    }

    public function test_receptionist_can_schedule_appointment_with_new_unregistered_customer(): void
    {
        $this->actingAs($this->receptionistUser, 'sanctum');

        $payload = [
            'customer' => [
                'legal_name' => 'Ana Beatriz Martínez',
                'phone' => '7890-5555',
                'address' => 'Colonia San Benito, Pasaje 3 #12',
                'municipality' => 'San Salvador Centro',
                'department' => 'San Salvador',
                'email' => 'ana.martinez@example.com',
            ],
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-25',
            'appointment_time' => '14:30',
            'notes' => 'Cliente llegó por anuncio de Facebook',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.customer.legalName', 'Ana Beatriz Martínez')
            ->assertJsonPath('data.customer.phone', '7890-5555')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.origin', 'manual')
            ->assertJsonPath('data.quoteId', null);

        // Verifica que se creó el cliente
        $this->assertDatabaseHas('customers', [
            'legal_name' => 'Ana Beatriz Martínez',
            'phone' => '7890-5555',
        ]);

        $createdCustomer = Customer::where('phone', '7890-5555')->firstOrFail();

        // Verifica que CustomerService inicializó billetera y puntos de lealtad
        $this->assertDatabaseHas('wallets', [
            'customer_id' => $createdCustomer->id,
            'cash_balance' => 0.00,
        ]);
        $this->assertDatabaseHas('loyalty_points', [
            'customer_id' => $createdCustomer->id,
            'points_balance' => 0,
        ]);

        // Verifica que la cita tiene asignado el customer_id recién creado
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $createdCustomer->id,
            'employee_id' => $this->technician->id,
            'origin' => 'manual',
            'status' => 'pending',
        ]);
    }

    public function test_can_schedule_appointment_with_inspection_flag_creates_inspection_record(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Corporación Verde SA de CV',
            'phone' => '7222-3333',
            'address' => 'Boulevard Los Próceres #10',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-28',
            'appointment_time' => '08:30',
            'is_inspection' => true,
            'notes' => 'Inspección fitosanitaria de palmeras',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertCreated();

        $appointmentId = $response->json('data.id');

        $inspection = Inspection::where('appointment_id', $appointmentId)->firstOrFail();
        $this->assertEquals($customer->id, $inspection->customer_id);
        $this->assertEquals($this->technician->id, $inspection->employee_id);
        $this->assertEquals('2026-11-28', $inspection->inspection_date->format('Y-m-d'));
        $this->assertEquals('Inspección fitosanitaria de palmeras', $inspection->findings);
    }

    public function test_can_schedule_appointment_with_cancelled_or_annulled_status(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente de Prueba Estados',
            'phone' => '7333-4444',
            'address' => 'Santa Elena, Calle Conchagua',
            'municipality' => 'Antiguo Cuscatlán',
            'status' => 'active',
        ]);

        $payloadCancelled = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-22',
            'appointment_time' => '09:00',
            'status' => 'cancelled',
            'notes' => 'Cita cancelada a petición de cliente',
        ];

        $responseCancelled = $this->postJson('/api/v1/appointments', $payloadCancelled);
        $responseCancelled->assertCreated()
            ->assertJsonPath('data.status', 'cancelled');

        $payloadAnnulled = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-23',
            'appointment_time' => '11:00',
            'status' => 'annulled',
            'notes' => 'Cita anulada por error de horario',
        ];

        $responseAnnulled = $this->postJson('/api/v1/appointments', $payloadAnnulled);
        $responseAnnulled->assertCreated()
            ->assertJsonPath('data.status', 'annulled');
    }

    public function test_unauthorized_roles_are_forbidden_with_403(): void
    {
        $this->actingAs($this->auditorUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Bloqueado',
            'phone' => '7999-8888',
            'address' => 'Calle Principal #1',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);
        $response->assertForbidden();
    }

    public function test_validation_fails_when_neither_customer_nor_customer_id_provided(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'customer']);
    }

    public function test_validation_fails_when_both_customer_id_and_customer_provided(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Conflicto',
            'phone' => '7555-6666',
            'address' => 'Calle El Mirador #1',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'customer' => [
                'legal_name' => 'Otro Cliente',
                'phone' => '7666-7777',
                'address' => 'Otra Dirección',
                'municipality' => 'San Salvador',
            ],
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'customer']);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $response = $this->postJson('/api/v1/appointments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id', 'appointment_date', 'appointment_time']);
    }

    public function test_fails_if_assigned_employee_is_not_field_technician(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Test Rol',
            'phone' => '7111-2222',
            'address' => 'Dirección Test',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        // Intentar asignar a un usuario con rol Administrador o Recepcionista en vez de Técnico de campo
        $nonTechEmployee = $this->adminUser->employee;

        $payload = [
            'customer_id' => $customer->id,
            'employee_id' => $nonTechEmployee->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
        ];

        $response = $this->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id'])
            ->assertJsonPath('errors.employee_id.0', 'El colaborador asignado debe ser un Técnico de campo activo.');
    }

    public function test_fails_if_technician_has_another_appointment_within_5_hours_same_day(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Conflicto Horario',
            'phone' => '7444-5555',
            'address' => 'Calle Los Bambúes #12',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        // Cita existente a las 09:00
        Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-01',
            'appointment_time' => '09:00:00',
            'status' => 'confirmed',
            'origin' => 'manual',
        ]);

        // Intentar programar a las 11:30 (diferencia de solo 2.5 horas, menor a 5 horas)
        $payloadConflict = [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-01',
            'appointment_time' => '11:30',
        ];

        $response = $this->postJson('/api/v1/appointments', $payloadConflict);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_time'])
            ->assertJsonPath('errors.appointment_time.0', 'El técnico de campo ya tiene una cita asignada a las 09:00 en esta fecha. Debe existir un intervalo mínimo de 5 horas entre cada visita.');
    }

    public function test_allows_scheduling_multiple_appointments_with_5_hours_grace_period(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Agenda Completa',
            'phone' => '7888-9999',
            'address' => 'Residencial Las Palmas',
            'municipality' => 'Santa Tecla',
            'status' => 'active',
        ]);

        // Cita 1: 08:00
        $response1 = $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-05',
            'appointment_time' => '08:00',
        ]);
        $response1->assertCreated();

        // Cita 2: 13:00 (exactamente 5 horas después)
        $response2 = $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-05',
            'appointment_time' => '13:00',
        ]);
        $response2->assertCreated();

        // Cita 3: 18:00 (exactamente 5 horas después de la segunda)
        $response3 = $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-05',
            'appointment_time' => '18:00',
        ]);
        $response3->assertCreated();

        $this->assertDatabaseCount('appointments', 3);
    }

    public function test_allows_scheduling_at_same_time_if_previous_appointment_is_cancelled_or_annulled(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $customer = Customer::create([
            'legal_name' => 'Cliente Cita Cancelada Previa',
            'phone' => '7123-9999',
            'address' => 'Calle La Reforma',
            'municipality' => 'San Salvador',
            'status' => 'active',
        ]);

        // Cita previa en estado cancelado a las 10:00
        Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-10',
            'appointment_time' => '10:00:00',
            'status' => 'cancelled',
            'origin' => 'manual',
        ]);

        // Nueva cita a la misma hora (10:00) debe permitirse porque el horario está liberado
        $response = $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-10',
            'appointment_time' => '10:00',
            'status' => 'pending',
        ]);

        $response->assertCreated();
    }
}
