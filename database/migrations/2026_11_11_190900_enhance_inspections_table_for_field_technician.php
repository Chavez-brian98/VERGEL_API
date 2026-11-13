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
        Schema::table('inspections', function (Blueprint $table) {
            $table->decimal('terrain_width', 10, 2)->nullable()->after('findings');
            $table->decimal('terrain_length', 10, 2)->nullable()->after('terrain_width');
            $table->decimal('total_area', 10, 2)->nullable()->after('terrain_length');
            $table->string('area_unit', 20)->default('m2')->after('total_area');
            $table->text('terrain_notes')->nullable()->after('area_unit');
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft')->after('terrain_notes');
        });

        Schema::create('inspection_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->integer('suggested_quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'service_id']);
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('inspection_services');

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'terrain_width',
                'terrain_length',
                'total_area',
                'area_unit',
                'terrain_notes',
                'status',
            ]);
        });
    }
};
