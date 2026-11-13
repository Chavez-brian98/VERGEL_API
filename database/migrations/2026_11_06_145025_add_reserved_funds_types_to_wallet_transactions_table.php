<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega tipos de transacción para mover fondos entre saldo disponible
     * y saldo reservado de suscripción.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM('top_up','annual_fee','maintenance_deduction','adjustment','reserve_funds','spend_reserved_funds') NOT NULL");

            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'top_up',
                'annual_fee',
                'maintenance_deduction',
                'adjustment',
                'reserve_funds',
                'spend_reserved_funds',
            ])->change();
        });
    }

    /**
     * Revierte los tipos agregados al enum de wallet_transactions.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM('top_up','annual_fee','maintenance_deduction','adjustment') NOT NULL");

            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'top_up',
                'annual_fee',
                'maintenance_deduction',
                'adjustment',
            ])->change();
        });
    }
};
