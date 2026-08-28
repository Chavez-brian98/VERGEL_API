<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes');
            $table->foreignId('employee_id')->nullable()->constrained('employees');
            $table->dateTime('payment_date')->useCurrent();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices definidos en el diseño
            $table->index('quote_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};