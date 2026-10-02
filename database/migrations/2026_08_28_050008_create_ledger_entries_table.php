<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('entry_type', ['sale', 'purchase', 'expense']);
            $table->foreignId('invoice_id')->nullable()->constrained('dte_invoices');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases');
            $table->foreignId('expense_id')->nullable()->constrained('expense_tickets');
            $table->decimal('amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            // Índices definidos en el diseño
            $table->index('entry_type');
            $table->index('created_at');
            $table->index(['entry_type', 'created_at'], 'idx_ledger_type_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
