<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de canje de EcoPuntos. Los puntos se descuentan al aprobar
     * y se devuelven con un movimiento compensatorio al cancelar.
     */
    public function up(): void
    {
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('reward_id')->constrained('loyalty_rewards')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('appointment_id')->nullable()->unique()->constrained('appointments')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null')->onUpdate('cascade');
            $table->unsignedInteger('points_spent');
            $table->enum('status', ['requested', 'approved', 'fulfilled', 'cancelled'])->default('requested');
            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('fulfilled_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('reward_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
    }
};
