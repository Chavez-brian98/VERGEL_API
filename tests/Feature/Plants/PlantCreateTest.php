<?php

namespace Tests\Feature\Plants;

use App\Models\Employee;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlantCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = Role::create(['role_name' => 'Admin']);
        $employee = Employee::create(['full_name' => 'Empleado Admin', 'role_id' => $role->id]);
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user, 'sanctum');
    }

    public function test_it_creates_a_plant_without_image(): void
    {
        $payload = [
            'name' => 'Ficus Lyrata',
            'price' => 25.50,
            'category_type' => 'Interior',
            'active' => true,
        ];

        $response = $this->postJson('/api/v1/plants', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Ficus Lyrata')
            ->assertJsonPath('data.price', 25.5)
            ->assertJsonPath('data.categoryType', 'Interior')
            ->assertJsonPath('data.imageUrl', null)
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('plants', [
            'name' => 'Ficus Lyrata',
            'price' => 25.50,
            'category_type' => 'Interior',
            'image_url' => null,
            'active' => 1,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'plants',
            'action' => 'insert',
        ]);
    }

    public function test_it_creates_a_plant_with_image_upload(): void
    {
        $file = UploadedFile::fake()->image('monstera.png', 600, 600);

        $payload = [
            'name' => 'Monstera Deliciosa',
            'price' => 35.00,
            'category_type' => 'Ornamental',
            'image' => $file,
        ];

        $response = $this->postJson('/api/v1/plants', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Monstera Deliciosa')
            ->assertJsonPath('data.price', 35);

        $plant = Plant::where('name', 'Monstera Deliciosa')->first();
        $this->assertNotNull($plant);
        $this->assertNotNull($plant->image_url);

        Storage::disk('public')->assertExists($plant->image_url);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/plants', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price'])
            ->assertJsonFragment([
                'name' => ['El nombre de la planta es obligatorio.'],
            ])
            ->assertJsonFragment([
                'price' => ['El precio de la planta es obligatorio.'],
            ]);
    }

    public function test_it_rejects_duplicate_plant_names(): void
    {
        Plant::create([
            'name' => 'Sansevieria Trifasciata',
            'price' => 12.00,
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/plants', [
            'name' => 'Sansevieria Trifasciata',
            'price' => 15.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['Ya existe una planta registrada con este nombre.'],
            ]);
    }

    public function test_it_rejects_invalid_image_file(): void
    {
        $fakeFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/plants', [
            'name' => 'Palma Areca',
            'price' => 19.99,
            'image' => $fakeFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_guests_cannot_create_plants(): void
    {
        Auth::forgetGuards();

        $response = $this->postJson('/api/v1/plants', [
            'name' => 'Cactus',
            'price' => 5.00,
        ]);

        $response->assertUnauthorized();
    }
}
