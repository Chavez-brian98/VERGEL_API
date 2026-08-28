<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_code', 20)->unique();
            $table->string('plan_name', 100);
            $table->integer('frequency_value');
            $table->enum('frequency_unit', ['days', 'weeks', 'months']);
            $table->integer('visit_count');
            $table->string('maintenance_type', 100)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('loyalty_points_multiplier', 4, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes(); // deleted_at
            $table->timestamps();  // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
