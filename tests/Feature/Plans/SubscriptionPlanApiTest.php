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
