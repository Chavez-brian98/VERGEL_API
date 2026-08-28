<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 20)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('applied_plan_id')->constrained('subscription_plans')->onDelete('restrict')->onUpdate('cascade');
            $table->decimal('base_price', 10, 2)->default(0.00);
            $table->decimal('discounted_subtotal', 10, 2)->default(0.00);
            $table->boolean('includes_vat')->default(false);
            $table->integer('loyalty_points_used')->default(0);
            $table->integer('loyalty_points_earned')->default(0);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
