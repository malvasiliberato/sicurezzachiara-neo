<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ateco_2025')) {
            return;
        }

        Schema::create('ateco_2025', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 10)->unique('ateco_2025_codice_uq');
            $table->string('titolo_it', 255);
            $table->string('titolo_en', 255)->nullable();
            $table->smallInteger('livello');
            $table->string('codice_padre', 10)->nullable();
            $table->smallInteger('livello_padre')->nullable();
            $table->integer('ordine');
            $table->timestamps();

            $table->index('codice_padre', 'ateco_2025_codice_padre_idx');
            $table->index('livello', 'ateco_2025_livello_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ateco_2025')) {
            return;
        }

        Schema::dropIfExists('ateco_2025');
    }
};
