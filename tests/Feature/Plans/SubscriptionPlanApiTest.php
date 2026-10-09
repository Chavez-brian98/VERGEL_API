<?php

namespace Tests\Feature\Plans;

use App\Models\Customer;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_it_returns_paginated_active_plans_with_subscriber_count(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-OFF',
            'active' => false,
        ]));

        Customer::create($this->customerData(['current_plan_id' => $plan->id]));

        $response = $this->getJson('/api/v1/subscription-plans');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $plan->id,
                'planCode' => 'PLAN-BASIC',
                'activeSubscribersCount' => 1,
            ]);
    }

    public function test_it_creates_a_plan(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', $this->planData());

        $response->assertCreated()
            ->assertJsonFragment([
                'planCode' => 'PLAN-BASIC',
                'visitCount' => 1,
                'active' => true,
            ]);

        $this->assertDatabaseHas('subscription_plans', [
            'plan_code' => 'PLAN-BASIC',
        ]);
    }

    public function test_it_validates_create_payload(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'plan_name' => '',
            'frequency_value' => 0,
            'frequency_unit' => 'years',
            'visit_count' => 0,
            'discount_percentage' => 120,
            'loyalty_points_multiplier' => 0.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'plan_code',
                'plan_name',
                'frequency_value',
                'frequency_unit',
                'visit_count',
                'discount_percentage',
                'loyalty_points_multiplier',
            ]);
    }

    public function test_it_rejects_duplicate_plan_code(): void
    {
        SubscriptionPlan::create($this->planData());

        $response = $this->postJson('/api/v1/subscription-plans', $this->planData());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    public function test_it_shows_a_plan_with_usage_stats(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        Customer::create($this->customerData(['current_plan_id' => $plan->id]));

        $response = $this->getJson("/api/v1/subscription-plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $plan->id,
                'planName' => 'Mantenimiento Mensual Básico',
                'activeSubscribersCount' => 1,
            ]);
    }

    public function test_it_updates_a_plan(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", [
            'discount_percentage' => 12.5,
            'visit_count' => 3,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $plan->id,
                'visitCount' => 3,
            ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'visit_count' => 3,
        ]);
    }

    public function test_it_updates_a_plan_keeping_its_own_code(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", [
            'plan_code' => 'PLAN-BASIC',
            'plan_name' => 'Mantenimiento Mensual Básico v2',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $plan->id,
                'planCode' => 'PLAN-BASIC',
                'planName' => 'Mantenimiento Mensual Básico v2',
            ]);
    }

    public function test_it_updates_plan_status(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $response = $this->patchJson("/api/v1/subscription-plans/{$plan->id}/status", [
            'active' => false,
        ]);

        $response->assertOk()->assertJsonFragment(['active' => false]);
    }

    public function test_it_soft_deletes_a_plan(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $response = $this->deleteJson("/api/v1/subscription-plans/{$plan->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
    }

    public function test_it_blocks_deletion_when_there_are_active_subscribers(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        Customer::create($this->customerData(['current_plan_id' => $plan->id]));

        $response = $this->deleteJson("/api/v1/subscription-plans/{$plan->id}");

        $response->assertStatus(409);
        $this->assertNotSoftDeleted('subscription_plans', ['id' => $plan->id]);
    }

    public function test_it_lists_subscribers_of_a_plan(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        $subscriber = Customer::create($this->customerData(['current_plan_id' => $plan->id]));

        $response = $this->getJson("/api/v1/subscription-plans/{$plan->id}/subscribers");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $subscriber->id,
                'legalName' => 'Cliente de prueba',
            ]);
    }

    public function test_it_filters_plans_by_search(): void
    {
        SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-PREMIUM',
            'plan_name' => 'Mantenimiento Quincenal Premium',
            'frequency_unit' => 'weeks',
        ]));

        $response = $this->getJson('/api/v1/subscription-plans?search=premium');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['planCode' => 'PLAN-PREMIUM']);
    }

    public function test_it_lists_inactive_plans_when_active_filter_is_false(): void
    {
        SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-OFF',
            'active' => false,
        ]));

        $response = $this->getJson('/api/v1/subscription-plans?active=0');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['planCode' => 'PLAN-OFF', 'active' => false]);
    }

    public function test_it_paginates_plans_using_the_limit_parameter(): void
    {
        SubscriptionPlan::create($this->planData(['plan_code' => 'PLAN-1']));
        SubscriptionPlan::create($this->planData(['plan_code' => 'PLAN-2']));
        SubscriptionPlan::create($this->planData(['plan_code' => 'PLAN-3']));

        $response = $this->getJson('/api/v1/subscription-plans?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_returns_404_when_showing_a_missing_plan(): void
    {
        $this->getJson('/api/v1/subscription-plans/99999')->assertNotFound();
    }

    public function test_it_returns_404_when_updating_a_missing_plan(): void
    {
        $this->putJson('/api/v1/subscription-plans/99999', [
            'visit_count' => 2,
        ])->assertNotFound();
    }

    public function test_it_returns_404_when_deleting_a_missing_plan(): void
    {
        $this->deleteJson('/api/v1/subscription-plans/99999')->assertNotFound();
    }

    public function test_it_returns_404_when_listing_subscribers_of_a_missing_plan(): void
    {
        $this->getJson('/api/v1/subscription-plans/99999/subscribers')->assertNotFound();
    }

    public function test_it_rejects_duplicate_plan_code_on_update(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-PREMIUM',
            'plan_name' => 'Mantenimiento Quincenal Premium',
        ]));

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", [
            'plan_code' => 'PLAN-PREMIUM',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    public function test_it_validates_frequency_unit_on_create(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', $this->planData([
            'frequency_unit' => 'years',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency_unit']);
    }

    public function test_it_defaults_active_to_true_on_create(): void
    {
        $data = $this->planData();
        unset($data['active']);

        $response = $this->postJson('/api/v1/subscription-plans', $data);

        $response->assertCreated()
            ->assertJsonFragment(['active' => true]);
    }

    public function test_it_validates_status_payload(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $response = $this->patchJson("/api/v1/subscription-plans/{$plan->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['active']);
    }

    public function test_it_rejects_reusing_a_code_from_a_soft_deleted_plan(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        $plan->delete();

        $response = $this->postJson('/api/v1/subscription-plans', $this->planData());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    private function planData(array $overrides = []): array
    {
        return array_merge([
            'plan_code' => 'PLAN-BASIC',
            'plan_name' => 'Mantenimiento Mensual Básico',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'maintenance_type' => 'Jardinería',
            'discount_percentage' => 5.00,
            'loyalty_points_multiplier' => 1.00,
            'active' => true,
        ], $overrides);
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Cliente de prueba',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2222',
        ], $overrides);
    }
}
