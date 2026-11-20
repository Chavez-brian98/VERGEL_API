<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Plant;
use App\Models\Quote;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seeders_run_cleanly_and_populate_expected_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Clientes y entidades principales
        $this->assertGreaterThanOrEqual(50, Customer::count());
        $this->assertGreaterThanOrEqual(50, Plant::count());
        $this->assertGreaterThanOrEqual(50, Quote::count());
        $this->assertGreaterThanOrEqual(50, Inspection::count());
        $this->assertGreaterThanOrEqual(50, Appointment::count());
        $this->assertGreaterThanOrEqual(50, AuditLog::count());

        // Catálogos
        $this->assertGreaterThanOrEqual(20, Service::count());
        $this->assertGreaterThanOrEqual(5, SubscriptionPlan::count());
        $this->assertGreaterThanOrEqual(10, Supplier::count());
        $this->assertGreaterThanOrEqual(10, User::count());
    }
}
