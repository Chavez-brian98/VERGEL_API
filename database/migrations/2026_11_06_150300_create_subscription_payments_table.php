<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra cada abono real recibido por una suscripción. Permite pagos
     * parciales y conserva el método y el empleado que los recibió.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null')->onUpdate('cascade');
            $table->dateTime('payment_date')->useCurrent();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 40)->nullable();
            $table->boolean('is_annual_payment')->default(false);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
