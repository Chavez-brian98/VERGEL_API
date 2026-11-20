<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite representar suscripciones con pago parcial pendiente.
     */
    public function up(): void
    {
        $values = ['active', 'pending_payment', 'completed', 'cancelled'];

        if ($this->isMySql()) {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','pending_payment','completed','cancelled') NOT NULL DEFAULT 'active'");

            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) use ($values) {
            $table->enum('status', $values)->default('active')->change();
        });
    }

    public function down(): void
    {
        if ($this->isMySql()) {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active'");

            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active')->change();
        });
    }

    private function isMySql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
