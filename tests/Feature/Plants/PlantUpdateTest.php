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

class PlantUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = Role::create(['role_name' => 'Admin']);
        $this->employee = Employee::create(['full_name' => 'Empleado Admin', 'role_id' => $role->id]);
        $this->user = User::factory()->create(['employee_id' => $this->employee->id]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_it_updates_plant_fields_successfully(): void
    {
        $plant = Plant::create([
            'name' => 'Ficus Lyrata Original',
            'price' => 20.00,
            'category_type' => 'Interior',
            'active' => true,
        ]);

        $response = $this->putJson("/api/v1/plants/{$plant->id}", [
            'name' => 'Ficus Lyrata Editado',
            'price' => 29.99,
            'category_type' => 'Ornamental',
            'active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Ficus Lyrata Editado')
            ->assertJsonPath('data.price', 29.99)
            ->assertJsonPath('data.categoryType', 'Ornamental')
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'name' => 'Ficus Lyrata Editado',
            'price' => 29.99,
            'category_type' => 'Ornamental',
            'active' => 0,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'plants',
            'affected_record_id' => $plant->id,
            'action' => 'update',
        ]);
    }

    public function test_it_updates_plant_with_new_image_and_replaces_old_one(): void
    {
        $oldFile = UploadedFile::fake()->image('old_plant.png');
        $oldPath = $oldFile->store('plants', 'public');

        $plant = Plant::create([
            'name' => 'Monstera Deliciosa',
            'price' => 25.00,
            'category_type' => 'Interior',
            'image_url' => $oldPath,
            'active' => true,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('new_plant.png');

        $response = $this->postJson("/api/v1/plants/{$plant->id}", [
            'price' => 35.00,
            'image' => $newFile,
        ]);

        $response->assertOk();

        $freshPlant = $plant->fresh();
        $this->assertNotNull($freshPlant->image_url);
        $this->assertNotSame($oldPath, $freshPlant->image_url);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($freshPlant->image_url);
    }

    public function test_it_removes_plant_image_when_remove_image_is_true(): void
    {
        $file = UploadedFile::fake()->image('temp_plant.png');
        $path = $file->store('plants', 'public');

        $plant = Plant::create([
            'name' => 'Helecho',
            'price' => 12.00,
            'image_url' => $path,
            'active' => true,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->putJson("/api/v1/plants/{$plant->id}", [
            'remove_image' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.imageUrl', null);

        $this->assertNull($plant->fresh()->image_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_it_allows_updating_while_keeping_its_own_name(): void
    {
        $plant = Plant::create([
            'name' => 'Palma Areca',
            'price' => 15.00,
            'active' => true,
        ]);

        $response = $this->putJson("/api/v1/plants/{$plant->id}", [
            'name' => 'Palma Areca',
            'price' => 18.50,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price', 18.5);
    }

    public function test_it_rejects_duplicate_plant_name_from_another_plant(): void
    {
        Plant::create(['name' => 'Planta A', 'price' => 10.00, 'active' => true]);
        $plantB = Plant::create(['name' => 'Planta B', 'price' => 15.00, 'active' => true]);

        $response = $this->putJson("/api/v1/plants/{$plantB->id}", [
            'name' => 'Planta A',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['Ya existe otra planta registrada con este nombre.'],
            ]);
    }

    public function test_it_returns_404_when_plant_to_update_does_not_exist(): void
    {
        $response = $this->putJson('/api/v1/plants/99999', [
            'name' => 'Fantasma',
            'price' => 50.00,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_it_returns_404_when_plant_to_update_is_soft_deleted(): void
    {
        $plant = Plant::create([
            'name' => 'Planta Eliminada',
            'price' => 20.00,
            'active' => true,
        ]);
        $plant->delete();

        $response = $this->putJson("/api/v1/plants/{$plant->id}", [
            'price' => 25.00,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_guests_cannot_update_plants(): void
    {
        Auth::forgetGuards();

        $plant = Plant::create([
            'name' => 'Planta Segura',
            'price' => 30.00,
            'active' => true,
        ]);

        $response = $this->putJson("/api/v1/plants/{$plant->id}", [
            'price' => 40.00,
        ]);

        $response->assertUnauthorized();
    }
}
