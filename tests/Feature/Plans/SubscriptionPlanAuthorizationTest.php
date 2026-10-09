<?php

namespace Tests\Feature\Plans;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_plans(): void
    {
        $this->getJson('/api/v1/subscription-plans')->assertUnauthorized();
    }

    public function test_guests_cannot_create_plans(): void
    {
        $this->postJson('/api/v1/subscription-plans', [])->assertUnauthorized();
    }

    public function test_guests_cannot_delete_plans(): void
    {
        $this->deleteJson('/api/v1/subscription-plans/1')->assertUnauthorized();
    }
}
