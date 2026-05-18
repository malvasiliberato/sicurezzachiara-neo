<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_sites', 'street_number')) {
            return;
        }

        Schema::table('company_sites', function (Blueprint $table) {
            $table->string('street_number', 20)->nullable()->after('address_line');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('company_sites', 'street_number')) {
            return;
        }

        Schema::table('company_sites', function (Blueprint $table) {
            $table->dropColumn('street_number');
        });
    }
};
