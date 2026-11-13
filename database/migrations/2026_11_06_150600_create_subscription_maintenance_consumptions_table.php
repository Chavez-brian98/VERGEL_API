<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consumo monetario de cada mantenimiento completado. El costo se guarda
     * como fotografía histórica y la cita solo puede generar un consumo.
     */
    public function up(): void
    {
        Schema::create('subscription_maintenance_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions', 'id', 'smc_subscription_id_foreign')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('appointment_id')->unique('smc_appointment_id_unique')->constrained('appointments', 'id', 'smc_appointment_id_foreign')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees', 'id', 'smc_employee_id_foreign')->onDelete('set null')->onUpdate('cascade');
            $table->decimal('maintenance_cost', 10, 2);
            $table->foreignId('wallet_transaction_id')->unique('smc_wallet_transaction_id_unique')->constrained('wallet_transactions', 'id', 'smc_wallet_transaction_id_foreign')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index('subscription_id', 'smc_subscription_id_index');
            $table->index('employee_id', 'smc_employee_id_index');
            $table->index('created_at', 'smc_created_at_index');

            $table->index('subscription_id');
            $table->index('employee_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_maintenance_consumptions');
    }
};
