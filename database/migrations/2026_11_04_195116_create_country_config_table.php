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
        Schema::create('country_config', function (Blueprint $table) {
            $table->id();
            $table->string('country')->unique();
            $table->string('currency', 10);
            $table->decimal('tax_percentage', 10, 2);
            $table->decimal('card_fee_percentage', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_config');
    }
};
