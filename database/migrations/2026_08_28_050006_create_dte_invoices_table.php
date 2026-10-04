<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('employee_id')->constrained('employees');
            $table->enum('document_type', ['final_consumer', 'tax_credit', 'exempt']);
            $table->enum('environment', ['test', 'production']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('card_fee', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->string('control_number', 40)->nullable()->unique();
            $table->string('receipt_stamp', 80)->nullable();
            $table->dateTime('issue_date')->useCurrent();
            $table->enum('transaction_status', ['pending', 'paid'])->default('pending');
            $table->softDeletes();
            $table->timestamps();

            // Índices definidos en el diseño
            $table->index('customer_id');
            $table->index('employee_id');
            $table->index('issue_date');
            $table->index('control_number');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_invoices');
    }
};
