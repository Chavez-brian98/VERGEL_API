<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PortfolioPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TransactionPolicy;
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
        // Autorización del módulo de Historial de Transacciones (RBAC).
        Gate::policy(Quote::class, TransactionPolicy::class);

        // Autorización del módulo de Cartera (RBAC), con el mismo esquema que Clientes.
        Gate::define('viewPortfolio', fn (User $user) => app(PortfolioPolicy::class)->view($user));
        Gate::define('viewPortfolioMovements', fn (User $user) => app(PortfolioPolicy::class)->viewMovements($user));
        Gate::define('managePortfolio', fn (User $user) => app(PortfolioPolicy::class)->manage($user));
    }
}
