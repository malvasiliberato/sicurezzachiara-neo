<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_catalog_items', function (Blueprint $table) {
            $table->json('expected_measures')->nullable()->after('description');
        });

        Schema::table('risk_measures', function (Blueprint $table) {
            $table->string('expected_measure_code', 100)->nullable()->after('risk_catalog_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('risk_measures', function (Blueprint $table) {
            $table->dropColumn('expected_measure_code');
        });

        Schema::table('risk_catalog_items', function (Blueprint $table) {
            $table->dropColumn('expected_measures');
        });
    }
};
