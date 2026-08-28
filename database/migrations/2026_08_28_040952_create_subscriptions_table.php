<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('restrict')->onUpdate('cascade');
            $table->date('payment_date');
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('reserved_balance', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
