<?php

namespace Tests\Feature\Inspections;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $tecnicoUser;

    private User $consultaUser;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();
        $colaboradorRole = Role::where('role_name', 'Colaborador')->firstOrFail();
        $consultaRole = Role::create([
            'role_name' => 'Consulta',
            'description' => 'Rol de solo lectura.',
        ]);
        $tecnicoRole = Role::create([
            'role_name' => 'Tecnico de campo',
            'description' => 'Técnico de campo encargado de visitas e inspecciones.',
        ]);

        $this->adminUser = $this->createUserWithRole('Admin Field', $adminRole);
        $this->tecnicoUser = $this->createUserWithRole('Tecnico Field', $tecnicoRole);
        $this->consultaUser = $this->createUserWithRole('Consulta Field', $consultaRole);

        $this->customer = Customer::create([
            'legal_name' => 'Jardines de San Salvador S.A.',
            'trade_name' => 'Jardines SV',
            'address' => 'Col. Escalón, San Salvador',
            'municipality' => 'San Salvador',
            'phone' => '7111-2222',
        ]);

        $this->service = Service::create([
            'name' => 'Diseño y paisajismo integral',
            'price' => 150.00,
            'unit_of_measure' => 'servicio',
        ]);
    }

    public function test_guest_cannot_access_inspections(): void
    {
        $this->getJson('/api/v1/inspections')->assertUnauthorized();
        $this->postJson('/api/v1/inspections', [])->assertUnauthorized();
    }

    public function test_consulta_user_can_view_but_cannot_create_or_update(): void
    {
        $inspection = Inspection::create([
            'customer_id' => $this->customer->id,
            'inspection_date' => '2026-11-12',
            'findings' => 'Inspección preliminar',
            'terrain_width' => 10,
            'terrain_length' => 20,
            'total_area' => 200,
            'status' => 'draft',
        ]);

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/inspections')
            ->assertOk();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson("/api/v1/inspections/{$inspection->id}")
            ->assertOk()
            ->assertJsonPath('data.totalArea', 200);

        $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/inspections', [
                'customer_id' => $this->customer->id,
                'inspection_date' => '2026-11-12',
            ])
            ->assertForbidden();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->deleteJson("/api/v1/inspections/{$inspection->id}")
            ->assertForbidden();
    }

    public function test_tecnico_de_campo_can_create_inspection_with_dimensions_services_and_photos(): void
    {
        $photo1 = UploadedFile::fake()->image('terreno1.jpg', 600, 400);
        $photo2 = UploadedFile::fake()->image('terreno2.png', 600, 400);

        $payload = [
            'customer_id' => $this->customer->id,
            'inspection_date' => '2026-11-12',
            'findings' => 'El cliente desea renovación completa del jardín frontal con césped y plantas ornamentales.',
            'terrain_width' => 12.5,
            'terrain_length' => 10.0,
            'area_unit' => 'm2',
            'terrain_notes' => 'Terreno plano, buena exposición solar.',
            'status' => 'submitted',
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'suggested_quantity' => 1,
                    'notes' => 'Requiere nivelación previa.',
                ],
            ],
            'photos' => [$photo1, $photo2],
        ];

        $response = $this->actingAs($this->tecnicoUser, 'sanctum')
            ->postJson('/api/v1/inspections', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.customerId', $this->customer->id)
            ->assertJsonPath('data.totalArea', 125)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.id', $this->service->id)
            ->assertJsonCount(2, 'data.photos');

        $this->assertDatabaseHas('inspections', [
            'customer_id' => $this->customer->id,
            'total_area' => 125.00,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('inspection_services', [
            'service_id' => $this->service->id,
            'suggested_quantity' => 1,
        ]);

        $this->assertDatabaseCount('inspection_photos', 2);
    }

    public function test_tecnico_can_update_inspection_and_upload_additional_photos(): void
    {
        $inspection = Inspection::create([
            'customer_id' => $this->customer->id,
            'employee_id' => $this->tecnicoUser->employee_id,
            'inspection_date' => '2026-11-12',
            'findings' => 'Inicial',
            'terrain_width' => 10,
            'terrain_length' => 10,
            'total_area' => 100,
            'status' => 'draft',
        ]);

        $photo = UploadedFile::fake()->image('nueva_foto.jpg');

        $response = $this->actingAs($this->tecnicoUser, 'sanctum')
            ->postJson("/api/v1/inspections/{$inspection->id}/photos", [
                'photos' => [$photo],
            ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.photos');

        $this->assertDatabaseCount('inspection_photos', 1);

        $photoModel = $inspection->photos()->first();

        // Eliminar foto
        $this->actingAs($this->tecnicoUser, 'sanctum')
            ->deleteJson("/api/v1/inspections/{$inspection->id}/photos/{$photoModel->id}")
            ->assertOk();

        $this->assertDatabaseCount('inspection_photos', 0);
    }

    public function test_tecnico_cannot_delete_inspection_but_admin_can(): void
    {
        $inspection = Inspection::create([
            'customer_id' => $this->customer->id,
            'inspection_date' => '2026-11-12',
            'findings' => 'Para eliminar',
            'status' => 'draft',
        ]);

        // Técnico no puede eliminar la inspección (solo admin)
        $this->actingAs($this->tecnicoUser, 'sanctum')
            ->deleteJson("/api/v1/inspections/{$inspection->id}")
            ->assertForbidden();

        // Admin sí puede
        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/inspections/{$inspection->id}")
            ->assertOk();

        $this->assertSoftDeleted('inspections', ['id' => $inspection->id]);
    }

    private function createUserWithRole(string $name, Role $role): User
    {
        $employee = Employee::create([
            'full_name' => $name,
            'role_id' => $role->id,
            'active' => true,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
        ]);
    }
}
