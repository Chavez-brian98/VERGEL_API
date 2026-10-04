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
        Schema::create('loyalty_points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_points_id')->constrained('loyalty_points');
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->enum('transaction_type', [
                'earned_purchase',
                'redeemed',
                'referral',
                'promotional',
                'special_bonus',
            ]);
            $table->integer('points');
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices definidos en el diseño
            $table->index('loyalty_points_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_transactions');
    }
};
