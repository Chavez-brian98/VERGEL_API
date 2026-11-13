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

class AppointmentQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $receptionistUser;

    private User $auditorUser;

    private User $techUser1;

    private User $techUser2;

    private Employee $technician1;

    private Employee $technician2;

    private Customer $customer1;

    private Customer $customer2;

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
        $this->techUser1 = User::factory()->create(['employee_id' => $this->technician1->id]);

        $this->technician2 = Employee::create([
            'full_name' => 'Tecnico Mario',
            'role_id' => $techRole->id,
            'active' => true,
        ]);
        $this->techUser2 = User::factory()->create(['employee_id' => $this->technician2->id]);

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

        $this->customer1 = Customer::create([
            'legal_name' => 'Cliente Uno S.A.',
            'phone' => '7000-0001',
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $this->customer2 = Customer::create([
            'legal_name' => 'Cliente Dos S.A.',
            'phone' => '7000-0002',
            'address' => 'Santa Tecla',
            'municipality' => 'La Libertad Sur',
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

    public function test_admin_and_receptionist_can_list_appointments_with_pagination(): void
    {
        Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
            'origin' => Appointment::ORIGIN_MANUAL,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customerId',
                        'customer' => ['id', 'legalName'],
                        'employeeId',
                        'employee' => ['id', 'fullName'],
                        'serviceId',
                        'service' => ['id', 'name', 'price'],
                        'appointmentDate',
                        'appointmentTime',
                        'status',
                        'origin',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_filter_appointments_by_date_range(): void
    {
        // Cita dentro del rango
        $inRange = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        // Cita fuera del rango
        Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-12-01',
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($this->receptionistUser)
            ->getJson('/api/v1/appointments?start_date=2026-11-01&end_date=2026-11-30');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inRange->id);
    }

    public function test_can_filter_appointments_by_single_date(): void
    {
        $today = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-10',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-11',
            'appointment_time' => '08:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/appointments?date=2026-11-10');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $today->id);
    }

    public function test_can_filter_appointments_by_status(): void
    {
        $confirmed = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-16',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/appointments?status=confirmed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $confirmed->id);
    }

    public function test_can_filter_appointments_by_client_id_alias(): void
    {
        $cust1App = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        Appointment::create([
            'customer_id' => $this->customer2->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '14:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/appointments?client_id={$this->customer1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cust1App->id);
    }

    public function test_can_get_all_appointments_without_pagination_when_all_is_true(): void
    {
        Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/appointments?all=true');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonMissing(['meta']);
    }

    public function test_field_technician_only_sees_their_own_assigned_appointments_in_list(): void
    {
        $tech1App = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        Appointment::create([
            'customer_id' => $this->customer2->id,
            'employee_id' => $this->technician2->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '14:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        // Tecnico 1 solo debe ver su propia cita
        $response = $this->actingAs($this->techUser1)
            ->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tech1App->id);
    }

    public function test_admin_and_receptionist_can_view_single_appointment_detail(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'service_id' => $this->service->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->receptionistUser)
            ->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $appointment->id)
            ->assertJsonPath('data.customer.legalName', 'Cliente Uno S.A.')
            ->assertJsonPath('data.employee.fullName', 'Tecnico Carlos')
            ->assertJsonPath('data.service.name', 'Poda de Cesped');
    }

    public function test_field_technician_can_view_their_own_assigned_appointment(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->techUser1)
            ->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $appointment->id);
    }

    public function test_field_technician_cannot_view_another_technicians_appointment(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer1->id,
            'employee_id' => $this->technician1->id,
            'appointment_date' => '2026-11-15',
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        // Tecnico 2 intenta ver cita asignada a Tecnico 1
        $response = $this->actingAs($this->techUser2)
            ->getJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(403);
    }

    public function test_returns_404_when_appointment_does_not_exist(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/appointments/99999');

        $response->assertStatus(404)
            ->assertJsonPath('codigo', 404);
    }

    public function test_unauthenticated_user_cannot_access_appointments(): void
    {
        $this->getJson('/api/v1/appointments')
            ->assertStatus(401);

        $this->getJson('/api/v1/appointments/1')
            ->assertStatus(401);
    }
}
