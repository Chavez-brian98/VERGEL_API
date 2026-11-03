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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('trade_name', 150)->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('nrc', 20)->nullable();
            $table->string('economic_activity', 150)->nullable();
            $table->string('phone', 20);
            $table->string('email', 120)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Índices de búsqueda frecuente
            $table->index('name');
            $table->index('tax_id');
            $table->index('nrc');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
