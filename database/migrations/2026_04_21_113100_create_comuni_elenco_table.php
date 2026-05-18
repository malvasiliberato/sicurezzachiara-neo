<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comuni_elenco')) {
            return;
        }

        Schema::create('comuni_elenco', function (Blueprint $table) {
            $table->integer('istat');
            $table->string('cap', 11);
            $table->string('comune', 255)->nullable();
            $table->string('regione', 50)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('provincia_esteso', 50)->nullable();
            $table->string('cod_fisco', 10)->nullable();
            $table->string('comune_provincia', 255)->nullable();

            $table->primary('istat', 'comuni_elenco_pkey');
            $table->index('comune_provincia', 'comuni_elenco_comune_provincia_idx');
            $table->index('provincia_esteso', 'comuni_elenco_provincia_esteso_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comuni_elenco')) {
            return;
        }

        Schema::dropIfExists('comuni_elenco');
    }
};
