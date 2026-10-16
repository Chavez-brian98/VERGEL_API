<?php

namespace Tests\Feature\Plants;

use App\Models\Employee;
use App\Models\Plant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PlantDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['role_name' => 'Admin']);
        $this->employee = Employee::create(['full_name' => 'Empleado Admin', 'role_id' => $role->id]);
        $this->user = User::factory()->create(['employee_id' => $this->employee->id]);

        $this->actingAs($this->user, 'sanctum');
    }

    public function test_it_soft_deletes_a_plant(): void
    {
        $plant = Plant::create([
            'name' => 'Ficus Lyrata',
            'price' => 30.00,
            'category_type' => 'Interior',
            'active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/plants/{$plant->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('plants', [
            'id' => $plant->id,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'plants',
            'affected_record_id' => $plant->id,
            'action' => 'delete',
        ]);
    }

    public function test_it_blocks_deletion_when_plant_has_associated_quotes(): void
    {
        $plant = Plant::create([
            'name' => 'Monstera Deliciosa',
            'price' => 25.00,
            'category_type' => 'Interior',
            'active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'plan_code' => 'PLAN-DEL-TEST',
            'plan_name' => 'Plan de prueba',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'active' => true,
        ]);

        $quote = Quote::create([
            'quote_number' => 'COT-PLANT-001',
            'employee_id' => $this->employee->id,
            'applied_plan_id' => $plan->id,
            'base_price' => 50.00,
            'discounted_subtotal' => 50.00,
            'total' => 50.00,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'item_type' => 'plant',
            'plant_id' => $plant->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'total' => 50.00,
        ]);

        $response = $this->deleteJson("/api/v1/plants/{$plant->id}");

        $response->assertStatus(409)
            ->assertJsonFragment(['codigo' => 409])
            ->assertJsonValidationErrors(['plant']);

        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_returns_404_when_plant_to_delete_does_not_exist(): void
    {
        $response = $this->deleteJson('/api/v1/plants/99999');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_it_returns_404_when_plant_is_already_deleted(): void
    {
        $plant = Plant::create([
            'name' => 'Planta Ya Eliminada',
            'price' => 10.00,
            'active' => true,
        ]);
        $plant->delete();

        $response = $this->deleteJson("/api/v1/plants/{$plant->id}");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_guests_cannot_delete_plants(): void
    {
        Auth::forgetGuards();

        $plant = Plant::create([
            'name' => 'Planta Inmune',
            'price' => 20.00,
            'active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/plants/{$plant->id}");

        $response->assertUnauthorized();
    }
}
