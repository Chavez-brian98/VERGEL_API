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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained('service_categories');
            $table->decimal('price', 10, 2);
            $table->string('unit_of_measure', 40);
            $table->decimal('loyalty_points_multiplier', 4, 2)->default(1);
            $table->text('description')->nullable();
            $table->foreignId('country_config_id')->nullable()->constrained('country_configs');
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Índice único compuesto
            $table->unique(['name', 'price'], 'uq_service_name_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
