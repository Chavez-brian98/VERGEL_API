<?php

namespace Tests\Feature\Plans;

use App\Models\Customer;
use App\Models\SubscriptionPlan;
use App\Services\Plans\SubscriptionPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlanService $planService;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'full_name' => 'Empleado de pruebas',
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->planService = app(SubscriptionPlanService::class);
    }

    public function test_it_creates_a_plan_with_audit_log(): void
    {
        $plan = $this->planService->create($this->planData());

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'plan_code' => 'PLAN-BASIC',
            'active' => true,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'subscription_plans',
            'affected_record_id' => $plan->id,
            'action' => 'insert',
        ]);
    }

    public function test_it_creates_a_plan_without_audit_when_there_are_no_employees(): void
    {
        DB::table('employees')->delete();

        $plan = $this->planService->create($this->planData());

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_it_lists_active_plans_with_subscriber_count_by_default(): void
    {
        $active = SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-OFF',
            'active' => false,
        ]));

        Customer::create($this->customerData([
            'current_plan_id' => $active->id,
            'status' => 'active',
        ]));
        Customer::create($this->customerData([
            'phone' => '2222-9999',
            'current_plan_id' => $active->id,
            'status' => 'inactive',
        ]));

        $result = $this->planService->search([], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame(1, (int) $result->first()->active_subscribers_count);
    }

    public function test_it_filters_plans_by_search_and_frequency_unit(): void
    {
        SubscriptionPlan::create($this->planData());
        SubscriptionPlan::create($this->planData([
            'plan_code' => 'PLAN-PREMIUM',
            'plan_name' => 'Mantenimiento Quincenal Premium',
            'frequency_unit' => 'weeks',
        ]));

        $result = $this->planService->search([
            'search' => 'premium',
            'frequency_unit' => 'weeks',
        ], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame('PLAN-PREMIUM', $result->first()->plan_code);
    }

    public function test_it_updates_a_plan_and_logs_old_and_new_data(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $updated = $this->planService->update($plan->id, [
            'discount_percentage' => 15.5,
            'visit_count' => 4,
        ]);

        $this->assertSame(4, $updated->visit_count);
        $this->assertEquals(15.5, (float) $updated->discount_percentage);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $plan->id,
            'action' => 'update',
        ]);
    }

    public function test_it_toggles_plan_status(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $updated = $this->planService->updateStatus($plan->id, false);

        $this->assertFalse($updated->active);
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'active' => false,
        ]);
    }

    public function test_it_soft_deletes_a_plan_without_active_subscribers(): void
    {
        $plan = SubscriptionPlan::create($this->planData());

        $this->planService->delete($plan->id);

        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $plan->id,
            'action' => 'delete',
        ]);
    }

    public function test_it_blocks_deletion_when_there_are_active_subscribers(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        Customer::create($this->customerData([
            'current_plan_id' => $plan->id,
            'status' => 'active',
        ]));

        $this->expectException(ValidationException::class);

        $this->planService->delete($plan->id);
    }

    public function test_it_lists_customers_subscribed_to_a_plan(): void
    {
        $plan = SubscriptionPlan::create($this->planData());
        $subscriber = Customer::create($this->customerData([
            'current_plan_id' => $plan->id,
        ]));

        $result = $this->planService->subscribers($plan->id, 15);

        $this->assertSame(1, $result->total());
        $this->assertSame($subscriber->id, $result->first()->id);
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
