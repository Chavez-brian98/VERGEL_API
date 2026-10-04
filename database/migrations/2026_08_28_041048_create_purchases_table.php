<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('issue_date');
            $table->string('document_type', 40);
            $table->string('document_number', 40);
            $table->string('supplier_tax_id', 20)->nullable();
            $table->string('supplier_nrc', 20)->nullable();
            $table->string('supplier_name', 150);
            $table->foreignId('employee_id')->nullable()->constrained('employees');
            $table->decimal('taxed_amount', 10, 2)->default(0.00);
            $table->decimal('exempt_amount', 10, 2)->default(0.00);
            $table->decimal('non_taxable_amount', 10, 2)->default(0.00);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Índices definidos en el diseño
            $table->index('issue_date');
            $table->index('supplier_tax_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
