<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAutomaticSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Employee $technician;

    private SubscriptionPlan $plan;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $techRole = Role::where('role_name', 'Tecnico de campo')->firstOrFail();

        $adminEmp = Employee::create([
            'full_name' => 'Admin Usuario',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);
        $this->adminUser = User::factory()->create(['employee_id' => $adminEmp->id]);

        $this->technician = Employee::create([
            'full_name' => 'Tecnico Carlos',
            'role_id' => $techRole->id,
            'active' => true,
        ]);

        $this->plan = SubscriptionPlan::create([
            'plan_code' => 'PLAN-REC-01',
            'plan_name' => 'Plan Mantenimiento Quincenal',
            'frequency_value' => 15,
            'frequency_unit' => 'days',
            'visit_count' => 2,
            'maintenance_type' => 'Jardinería',
            'discount_percentage' => 10.00,
            'loyalty_points_multiplier' => 1.00,
            'active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Mantenimiento Integral',
            'price' => 60.00,
            'unit_of_measure' => 'm2',
            'points_multiplier' => 1.0,
            'is_active' => true,
        ]);
    }

    public function test_marking_appointment_as_completed_creates_next_automatic_appointment_for_active_customer(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Residencial Las Palmas',
            'phone' => '7111-2222',
            'address' => 'Santa Tecla',
            'municipality' => 'La Libertad Sur',
            'status' => 'active',
            'visit_frequency_value' => 15,
            'visit_frequency_unit' => 'days',
            'current_plan_id' => $this->plan->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-08-01',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'status' => Appointment::STATUS_COMPLETED,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.nextAppointment.appointmentDate', '2026-08-16')
            ->assertJsonPath('data.nextAppointment.appointmentTime', '09:00')
            ->assertJsonPath('data.nextAppointment.origin', 'automatic')
            ->assertJsonPath('data.nextAppointment.status', 'pending');

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'origin' => Appointment::ORIGIN_AUTOMATIC,
            'status' => Appointment::STATUS_PENDING,
        ]);

        $createdAuto = Appointment::where('origin', Appointment::ORIGIN_AUTOMATIC)->firstOrFail();
        $this->assertSame('2026-08-16', $createdAuto->appointment_date->format('Y-m-d'));
        $this->assertSame('09:00', substr($createdAuto->appointment_time, 0, 5));
    }

    public function test_marking_appointment_as_completed_does_not_create_appointment_for_inactive_customer(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Empresa Inactiva S.A.',
            'phone' => '7333-4444',
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'status' => 'inactive',
            'visit_frequency_value' => 15,
            'visit_frequency_unit' => 'days',
            'current_plan_id' => $this->plan->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-08-01',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'status' => Appointment::STATUS_COMPLETED,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonMissing(['nextAppointment'])
            ->assertJsonMissing(['suggestedRescheduleDates']);

        $this->assertDatabaseMissing('appointments', [
            'customer_id' => $customer->id,
            'origin' => Appointment::ORIGIN_AUTOMATIC,
        ]);
    }

    public function test_marking_appointment_as_completed_returns_suggested_dates_when_technician_is_not_available_on_ideal_date(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Condominio San Francisco',
            'phone' => '7555-6666',
            'address' => 'Antiguo Cuscatlán',
            'municipality' => 'La Libertad Este',
            'status' => 'active',
            'visit_frequency_value' => 14,
            'visit_frequency_unit' => 'days',
        ]);

        // Cita actual del cliente el 2026-08-01 a las 09:00. Fecha ideal siguiente: 2026-08-15 a las 09:00
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-08-01',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        // Ya existe una cita ocupando la fecha ideal (2026-08-15) del técnico a las 10:00 (dentro de las 5 horas)
        Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-08-15',
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $payload = [
            'status' => Appointment::STATUS_COMPLETED,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonMissing(['nextAppointment'])
            ->assertJsonPath('data.suggestedRescheduleDates.idealDate', '2026-08-15')
            ->assertJsonStructure([
                'data' => [
                    'suggestedRescheduleDates' => [
                        'idealDate',
                        'availableDates',
                        'message',
                    ],
                ],
            ]);

        // La fecha ideal 2026-08-15 no debe estar dentro de las fechas disponibles sugeridas
        $availableDates = $response->json('data.suggestedRescheduleDates.availableDates');
        $this->assertNotEmpty($availableDates);
        $this->assertNotContains('2026-08-15', $availableDates);

        // No se debe haber creado una nueva cita automática
        $this->assertDatabaseMissing('appointments', [
            'customer_id' => $customer->id,
            'origin' => Appointment::ORIGIN_AUTOMATIC,
        ]);
    }

    public function test_respects_weeks_frequency_unit(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Cliente Frecuencia Semanal',
            'phone' => '7777-8888',
            'address' => 'Santa Tecla',
            'municipality' => 'La Libertad Sur',
            'status' => 'active',
            'visit_frequency_value' => 2,
            'visit_frequency_unit' => 'weeks',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-08-01',
            'appointment_time' => '08:30',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", ['status' => 'completed']);

        // 2 semanas = 14 días (2026-08-01 + 14 días = 2026-08-15)
        $response->assertStatus(200)
            ->assertJsonPath('data.nextAppointment.appointmentDate', '2026-08-15');
    }

    public function test_uses_current_plan_frequency_when_customer_frequency_is_not_individually_defined(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Cliente con Plan',
            'phone' => '7888-9999',
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'status' => 'active',
            'visit_frequency_value' => null,
            'visit_frequency_unit' => null,
            'current_plan_id' => $this->plan->id, // Plan tiene 15 días
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'employee_id' => $this->technician->id,
            'appointment_date' => '2026-08-01',
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/appointments/{$appointment->id}", ['status' => 'completed']);

        $response->assertStatus(200)
            ->assertJsonPath('data.nextAppointment.appointmentDate', '2026-08-16');
    }
}
