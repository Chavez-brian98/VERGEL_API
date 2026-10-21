<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el valor "denied" al enum de audit_log.action para registrar
     * los intentos de acceso o modificación rechazados por las policies.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE audit_log MODIFY COLUMN action ENUM('insert','update','delete','restore','denied') NOT NULL");

            return;
        }

        Schema::table('audit_log', function (Blueprint $table) {
            $table->enum('action', ['insert', 'update', 'delete', 'restore', 'denied'])->change();
        });
    }

    /**
     * Revierte el valor agregado al enum de audit_log.action.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE audit_log MODIFY COLUMN action ENUM('insert','update','delete','restore') NOT NULL");

            return;
        }

        Schema::table('audit_log', function (Blueprint $table) {
            $table->enum('action', ['insert', 'update', 'delete', 'restore'])->change();
        });
    }
};
