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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 150);
            $table->string('trade_name', 150)->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('nrc', 20)->nullable();
            $table->string('economic_activity', 150)->nullable();
            $table->string('address', 255);
            $table->string('department', 60)->nullable();
            $table->string('municipality', 60);
            $table->string('phone', 20);
            $table->string('email', 120)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('visit_frequency_value')->nullable();
            $table->enum('visit_frequency_unit', ['days', 'weeks', 'months'])->nullable();
            $table->foreignId('current_plan_id')->nullable()->constrained('subscription_plans');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
