<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('item_type', ['service', 'plant']);
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('plant_id')->nullable()->constrained('plants')->onDelete('set null')->onUpdate('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
