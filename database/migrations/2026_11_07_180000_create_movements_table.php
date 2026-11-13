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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->enum('movement_type', ['sale', 'purchase', 'expense']);
            $table->nullableMorphs('movementable');
            $table->string('document_type', 60);
            $table->string('document_number', 60);
            $table->string('entity_name', 150);
            $table->string('entity_tax_id', 30)->nullable();
            $table->date('movement_date');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->string('status', 30)->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // Índices optimizados para consultas del libro general y cálculos en tiempo real
            $table->index(['movement_date', 'movement_type'], 'idx_movements_date_type');
            $table->index(['movement_type', 'status'], 'idx_movements_type_status');
            $table->index('entity_name', 'idx_movements_entity_name');
            $table->index('document_number', 'idx_movements_doc_number');
            $table->index('deleted_at', 'idx_movements_deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
