<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de servicios que pueden canjearse exclusivamente con EcoPuntos.
     */
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('reward_code', 30)->unique();
            $table->string('reward_name', 150);
            $table->text('description')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null')->onUpdate('cascade');
            $table->unsignedInteger('points_cost');
            $table->boolean('active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index('service_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
