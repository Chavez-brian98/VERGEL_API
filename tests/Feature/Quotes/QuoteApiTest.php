<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Plant;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $recepcionistaUser;

    private User $colaboradorUser;

    private Customer $customer;

    private SubscriptionPlan $plan;

    private Service $service;

    private Plant $plant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $colaboradorRole = Role::create([
            'role_name' => 'Colaborador',
            'description' => 'Rol operativo de solo lectura.',
        ]);
        $recepcionistaRole = Role::where('role_name', 'Recepcionista')->firstOrFail();

        $this->adminUser = $this->createUserWithRole('Admin User', $adminRole);
        $this->recepcionistaUser = $this->createUserWithRole('Recepcionista User', $recepcionistaRole);
        $this->colaboradorUser = $this->createUserWithRole('Colaborador User', $colaboradorRole);

        $this->customer = Customer::create([
            'legal_name' => 'Cliente Cotización S.A. de C.V.',
            'trade_name' => 'Cliente Cotización',
            'address' => 'Colonia Centro, San Salvador',
            'municipality' => 'San Salvador',
            'phone' => '7000-1111',
        ]);

        $this->plan = SubscriptionPlan::create([
            'plan_code' => 'PLAN-COT',
            'plan_name' => 'Plan Cotización',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 2,
            'discount_percentage' => 10,
            'loyalty_points_multiplier' => 1,
        ]);

        $this->service = Service::create([
            'name' => 'Poda de árbol',
            'price' => 50.00,
            'unit_of_measure' => 'servicio',
        ]);

        $this->plant = Plant::create([
            'name' => 'Planta de interior',
            'price' => 20.00,
        ]);
    }

    private function createUserWithRole(string $fullName, Role $role, bool $active = true): User
    {
        $employee = Employee::create([
            'full_name' => $fullName,
            'role_id' => $role->id,
            'active' => $active,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
        ]);
    }

    private function quotePayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'applied_plan_id' => $this->plan->id,
            'includes_vat' => false,
            'loyalty_points_used' => 0,
            'status' => 'pending',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $this->service->id,
                    'quantity' => 2,
                    'unit_price' => 50,
                ],
                [
                    'item_type' => 'plant',
                    'plant_id' => $this->plant->id,
                    'quantity' => 1,
                    'unit_price' => 20,
                ],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_access_quotes(): void
    {
        $this->getJson('/api/v1/quotes')->assertUnauthorized();
        $this->postJson('/api/v1/quotes', [])->assertUnauthorized();
    }

    public function test_colaborador_can_list_but_cannot_create_quotes(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertCreated();

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->getJson('/api/v1/quotes')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertForbidden();
    }

    public function test_recepcionista_can_create_quote_with_calculated_totals(): void
    {
        $response = $this->actingAs($this->recepcionistaUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload());

        $response->assertCreated()
            ->assertJsonPath('data.basePrice', 120)
            ->assertJsonPath('data.discountedSubtotal', 108)
            ->assertJsonPath('data.total', 122.04)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.items');

        $this->assertMatchesRegularExpression(
            '/^COT-\d{8}-\d{4}$/',
            $response->json('data.quoteNumber')
        );

        $this->assertDatabaseHas('quotes', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->recepcionistaUser->employee_id,
            'base_price' => 120,
            'discounted_subtotal' => 108,
            'total' => 122.04,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('quote_items', 2);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'insert',
            'affected_table' => 'quotes',
        ]);
    }

    public function test_it_shows_a_quote_with_relations_and_items(): void
    {
        $quoteId = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->json('data.id');

        $response = $this->actingAs($this->colaboradorUser, 'sanctum')
            ->getJson("/api/v1/quotes/{$quoteId}");

        $response->assertOk()
            ->assertJsonPath('data.id', $quoteId)
            ->assertJsonPath('data.customer.id', $this->customer->id)
            ->assertJsonPath('data.appliedPlan.id', $this->plan->id)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_it_updates_a_quote_and_recalculates_totals(): void
    {
        $quoteId = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->json('data.id');

        $response = $this->actingAs($this->recepcionistaUser, 'sanctum')
            ->putJson("/api/v1/quotes/{$quoteId}", [
                'includes_vat' => true,
                'items' => [
                    [
                        'item_type' => 'service',
                        'service_id' => $this->service->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.basePrice', 100)
            ->assertJsonPath('data.discountedSubtotal', 90)
            ->assertJsonPath('data.total', 90)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('audit_log', [
            'action' => 'update',
            'affected_table' => 'quotes',
            'affected_record_id' => $quoteId,
        ]);
    }

    public function test_it_updates_quote_status(): void
    {
        $quoteId = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->json('data.id');

        $response = $this->actingAs($this->recepcionistaUser, 'sanctum')
            ->patchJson("/api/v1/quotes/{$quoteId}/status", ['status' => 'paid']);

        $response->assertOk()->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('quotes', [
            'id' => $quoteId,
            'status' => 'paid',
        ]);
    }

    public function test_only_admin_or_recepcionista_can_delete_quote(): void
    {
        $quoteId = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->json('data.id');

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$quoteId}")
            ->assertForbidden();

        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$quoteId}")
            ->assertOk();

        $this->assertSoftDeleted('quotes', ['id' => $quoteId]);
    }

    public function test_loyalty_points_require_a_service_item(): void
    {
        $payload = $this->quotePayload([
            'loyalty_points_used' => 5,
            'items' => [
                [
                    'item_type' => 'plant',
                    'plant_id' => $this->plant->id,
                    'quantity' => 1,
                    'unit_price' => 20,
                ],
            ],
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('loyalty_points_used');
    }

    public function test_it_filters_quotes_by_status_and_customer(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertCreated();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/quotes', $this->quotePayload(['status' => 'paid']))
            ->assertCreated();

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/quotes?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/quotes?customer_id='.$this->customer->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
