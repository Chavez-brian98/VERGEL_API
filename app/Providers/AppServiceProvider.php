<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Supplier;
use App\Policies\CustomerPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\QuotePolicy;
use App\Policies\RolePolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Autorización del módulo de Clientes (RBAC).
        Gate::policy(Customer::class, CustomerPolicy::class);
        // Autorización del módulo de Proveedores (RBAC).
        Gate::policy(Supplier::class, SupplierPolicy::class);
        // Autorización del módulo de Empleados (RBAC).
        Gate::policy(Employee::class, EmployeePolicy::class);
        // Autorización del módulo de Roles (RBAC).
        Gate::policy(Role::class, RolePolicy::class);
        // Autorización del módulo de Cotizaciones (RBAC).
        Gate::policy(Quote::class, QuotePolicy::class);
    }
}
