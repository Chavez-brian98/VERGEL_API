<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Supplier;
use App\Policies\AuditLogPolicy;
use App\Policies\CustomerPolicy;
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
        // Autorización del módulo de Bitácoras de Auditoría (RBAC).
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
