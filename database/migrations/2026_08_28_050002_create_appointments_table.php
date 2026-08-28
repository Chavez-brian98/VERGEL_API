<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict')->onUpdate('cascade');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['pending', 'confirmed', 'completed'])->default('pending');
            $table->enum('origin', ['manual', 'quote', 'automatic'])->default('manual');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('appointment_date');
            $table->index('customer_id');
            $table->index('employee_id');
            $table->index(['appointment_date', 'appointment_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
