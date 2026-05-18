<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contact_pec')->nullable()->after('contact_email');
            $table->string('address_line')->nullable()->after('contact_phone');
            $table->string('street_number', 20)->nullable()->after('address_line');
            $table->string('postal_code', 20)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'contact_pec',
                'address_line',
                'street_number',
                'postal_code',
            ]);
        });
    }
};
