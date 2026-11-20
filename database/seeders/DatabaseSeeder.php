<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AuthTestUserSeeder::class,
            RolesAndUsersSeeder::class,
            CountryConfigSeeder::class,
            EmployeeAndUserSeeder::class,
            ServiceCatalogSeeder::class,
            PlantCatalogSeeder::class,
            SubscriptionPlanCatalogSeeder::class,
            SupplierCatalogSeeder::class,
            CustomerOperationalSeeder::class,
        ]);
    }
}
