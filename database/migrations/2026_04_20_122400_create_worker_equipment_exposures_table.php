<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_equipment_exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_asset_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['worker_id', 'equipment_asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_equipment_exposures');
    }
};
