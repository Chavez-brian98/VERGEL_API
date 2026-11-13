<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $receptionistUser;

    private User $auditorUser;

    private User $techUser;

    private Employee $technician1;

    private Employee $technician2;

    private Employee $receptionistEmp;

    private Customer $customer;

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

        $this->receptionistEmp = Employee::create([
            'full_name' => 'Recepcionista Usuario',
            'role_id' => $receptionistRole->id,
            'active' => true,
        ]);
        $this->receptionistUser = User::factory()->create(['employee_id' => $this->receptionistEmp->id]);

        $auditorEmp = Employee::create([
            'full_name' => 'Auditor Usuario',
            'role_id' => $auditorRole->id,
            'active' => true,
        ]);
        $this->auditorUser = User::factory()->create(['employee_id' => $auditorEmp->id]);

        $this->technician1 = Employee::create([
            'full_name' => 'Tecnico Carlos',
            'role_id' => $techRole->id,
            'active' => true,
        ]);
        $this->techUser = User::factory()->create(['employee_id' => $this->technician1->id]);

        $this->technician2 = Employee::create([
            'full_name' => 'Tecnico Mario',
            'role_id' => $techRole->id,
            'active' => true,
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
            'legal_name' => 'Cliente Pruebas',
            'phone' => '7000-0000',
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Poda de Cesped',
            'price' => 50.00,
            'unit_of_measure' => 'm2',
            'points_multiplier' => 1.0,
            'is_active' => true,
        ]);
    }

    public function test_admin_and_receptionist_can_reschedule_appointment_date_and_time(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
            'notes' => 'Nota original',
        ]);

        $payload = [
            'appointment_date' => '2026-11-25',
            'appointment_time' => '14:30',
            'notes' => 'Reprogramada para la tarde',
        ];

        $response = $this->actingAs($this->receptionistUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $appointment->id)
            ->assertJsonPath('data.appointmentDate', '2026-11-25')
            ->assertJsonPath('data.appointmentTime', '14:30')
            ->assertJsonPath('data.notes', 'Reprogramada para la tarde');

        $appointment->refresh();
        $this->assertSame('2026-11-25', $appointment->appointment_date->format('Y-m-d'));
        $this->assertSame('14:30', substr($appointment->appointment_time, 0, 5));
        $this->assertSame('Reprogramada para la tarde', $appointment->notes);
    }

    public function test_can_update_same_appointment_without_self_collision(): void
    {
        // Misma fecha, cambiando solo 1 hora; no debe colisionar consigo misma
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'appointment_time' => '09:00',
        ];

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.appointmentTime', '09:00');
    }

    public function test_can_reassign_technician_to_another_active_field_technician(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'employee_id' => $this->technician2->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.employeeId', $this->technician2->id)
            ->assertJsonPath('data.employee.fullName', 'Tecnico Mario');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'employee_id' => $this->technician2->id,
        ]);
    }

    public function test_cannot_reassign_technician_to_non_field_technician(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'employee_id' => $this->receptionistEmp->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }

    public function test_fails_when_rescheduled_time_conflicts_with_another_appointment_of_same_technician(): void
    {
        // Cita 1 del técnico 1 a las 08:00
        Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        // Cita 2 del técnico 1 en otra fecha
        $appointment2 = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-22',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        // Intentar reprogramar la cita 2 para el 2026-11-20 a las 10:00 (a solo 2 horas de la cita 1)
        $payload = [
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment2->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_time']);
    }

    public function test_can_update_status_to_cancelled_or_annulled(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'status' => Appointment::STATUS_CANCELLED,
            'notes' => 'Cliente canceló por motivos de lluvia',
        ];

        $response = $this->actingAs($this->receptionistUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_CANCELLED,
        ]);
    }

    public function test_can_update_status_to_completed(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'status' => Appointment::STATUS_COMPLETED,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_cannot_reschedule_completed_appointment(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_COMPLETED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'appointment_date' => '2026-11-22',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_updating_appointment_date_or_technician_syncs_linked_inspection(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $inspection = Inspection::create([
            'customer_id' => $this->customer->id,
            'appointment_id' => $appointment->id,
            'employee_id' => $this->technician1->id,
            'inspection_date' => '2026-11-20',
            'findings' => 'Inspección inicial',
        ]);

        $payload = [
            'appointment_date' => '2026-11-23',
            'employee_id' => $this->technician2->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'appointment_id' => $appointment->id,
            'employee_id' => $this->technician2->id,
            'inspection_date' => '2026-11-23',
        ]);
    }

    public function test_auditor_and_field_technician_cannot_update_appointment(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = ['notes' => 'Intento de edición'];

        $this->actingAs($this->auditorUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload)
            ->assertStatus(403);

        $this->actingAs($this->techUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload)
            ->assertStatus(403);
    }

    public function test_returns_404_when_appointment_does_not_exist(): void
    {
        $payload = ['notes' => 'No existe'];

        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/appointments/99999', $payload);

        $response->assertStatus(404)
            ->assertJsonPath('codigo', 404);
    }

    public function test_registers_audit_log_on_update(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-20',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
            'notes' => 'Nota original',
        ]);

        $payload = [
            'notes' => 'Nota modificada con auditoría',
        ];

        $this->actingAs($this->adminUser)
            ->putJson("/api/v1/appointments/{$appointment->id}", $payload)
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'update',
            'affected_table' => 'appointments',
            'affected_record_id' => $appointment->id,
        ]);
    }
}
