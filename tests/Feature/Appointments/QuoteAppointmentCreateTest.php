<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteAppointmentCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $receptionistUser;

    private User $auditorUser;

    private Employee $technician;

    private Customer $customer;

    private Quote $quote;

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
            'name' => 'Poda y Mantenimiento Integral',
            'price' => 75.00,
            'unit_of_measure' => 'm2',
        ]);

        $plan = SubscriptionPlan::create([
            'plan_code' => 'PLAN-TEST',
            'plan_name' => 'Plan Mantenimiento Básico',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'maintenance_type' => 'Jardinería',
            'discount_percentage' => 10.00,
            'loyalty_points_multiplier' => 1.00,
            'active' => true,
        ]);

        $this->customer = Customer::create([
            'legal_name' => 'Hacienda Los Laureles',
            'phone' => '7123-8888',
            'address' => 'Carretera al Puerto Km 12',
            'municipality' => 'Santa Tecla',
            'status' => 'active',
        ]);

        $this->quote = Quote::create([
            'quote_number' => 'COT-2026-0001',
            'customer_id' => $this->customer->id,
            'employee_id' => $adminEmp->id,
            'applied_plan_id' => $plan->id,
            'base_price' => 100.00,
            'discounted_subtotal' => 90.00,
            'total' => 90.00,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_schedule_appointment_from_quote(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '09:00',
            'status' => 'confirmed',
            'notes' => 'Ejecución del servicio según cotización aprobada',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.quoteId', $this->quote->id)
            ->assertJsonPath('data.customerId', $this->customer->id)
            ->assertJsonPath('data.employeeId', $this->technician->id)
            ->assertJsonPath('data.serviceId', $this->service->id)
            ->assertJsonPath('data.origin', 'quote')
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.notes', 'Ejecución del servicio según cotización aprobada');

        $this->assertDatabaseHas('appointments', [
            'quote_id' => $this->quote->id,
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician->id,
            'service_id' => $this->service->id,
            'origin' => 'quote',
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'appointments',
            'action' => 'insert',
        ]);
    }

    public function test_receptionist_can_schedule_appointment_from_quote_with_default_notes(): void
    {
        $this->actingAs($this->receptionistUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-22',
            'appointment_time' => '14:00',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.quoteId', $this->quote->id)
            ->assertJsonPath('data.customerId', $this->customer->id)
            ->assertJsonPath('data.origin', 'quote')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.notes', 'Cita programada desde cotización COT-2026-0001');

        $this->assertDatabaseHas('appointments', [
            'quote_id' => $this->quote->id,
            'customer_id' => $this->customer->id,
            'origin' => 'quote',
            'status' => 'pending',
            'notes' => 'Cita programada desde cotización COT-2026-0001',
        ]);
    }

    public function test_fails_if_assigned_employee_is_not_field_technician(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $nonTechEmp = $this->adminUser->employee;

        $payload = [
            'employee_id' => $nonTechEmp->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '09:00',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id'])
            ->assertJsonPath('errors.employee_id.0', 'El colaborador asignado debe ser un Técnico de campo activo.');
    }

    public function test_fails_if_technician_has_another_appointment_within_5_hours_same_day(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Cita existente a las 09:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-25',
            'appointment_time' => '09:00:00',
            'status' => 'confirmed',
            'origin' => 'manual',
        ]);

        // Intentar programar a las 12:00 (diferencia de solo 3 horas)
        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-25',
            'appointment_time' => '12:00',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_time'])
            ->assertJsonPath('errors.appointment_time.0', 'El técnico de campo ya tiene una cita asignada a las 09:00 en esta fecha. Debe existir un intervalo mínimo de 5 horas entre cada visita.');
    }

    public function test_returns_404_if_quote_does_not_exist(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '09:00',
        ];

        $response = $this->postJson('/api/v1/quotes/99999/appointments', $payload);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Cotización no encontrada');
    }

    public function test_unauthorized_roles_are_forbidden_with_403(): void
    {
        $this->actingAs($this->auditorUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '09:00',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);
        $response->assertForbidden();
    }

    public function test_can_schedule_from_quote_with_inspection_flag_creates_inspection_record(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-11-30',
            'appointment_time' => '10:00',
            'is_inspection' => true,
            'notes' => 'Inspección de avance de cotización',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);
        $response->assertCreated();

        $appointmentId = $response->json('data.id');

        $inspection = Inspection::where('appointment_id', $appointmentId)->firstOrFail();
        $this->assertEquals($this->customer->id, $inspection->customer_id);
        $this->assertEquals($this->technician->id, $inspection->employee_id);
        $this->assertEquals('2026-11-30', $inspection->inspection_date->format('Y-m-d'));
        $this->assertEquals('Inspección de avance de cotización', $inspection->findings);
    }

    public function test_fails_if_quote_already_has_an_active_pending_or_confirmed_appointment(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Cita activa existente para esta cotización
        Appointment::create([
            'quote_id' => $this->quote->id,
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-01',
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
            'origin' => 'quote',
        ]);

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-05',
            'appointment_time' => '10:00',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quote'])
            ->assertJsonPath('errors.quote.0', 'Esta cotización ya tiene una cita activa programada para el 2026-12-01 a las 10:00. Si requiere cambiar la fecha u horario, utilice la opción de reprogramar.');
    }

    public function test_allows_scheduling_from_quote_if_previous_appointment_is_cancelled_or_annulled(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        // Cita previa de la cotización pero cancelada
        Appointment::create([
            'quote_id' => $this->quote->id,
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-01',
            'appointment_time' => '10:00:00',
            'status' => 'cancelled',
            'origin' => 'quote',
        ]);

        $payload = [
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-12-05',
            'appointment_time' => '10:00',
            'status' => 'pending',
        ];

        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/appointments", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.quoteId', $this->quote->id)
            ->assertJsonPath('data.status', 'pending');
    }
}
