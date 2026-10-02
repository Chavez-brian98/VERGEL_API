<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('category', 80);
            $table->string('description', 255)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->softDeletes();
            $table->timestamps();

            // Índices definidos en el diseño
            $table->index('expense_date');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_tickets');
    }
};
