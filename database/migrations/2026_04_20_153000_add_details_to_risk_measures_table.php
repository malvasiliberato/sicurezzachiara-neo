<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_measures', function (Blueprint $table) {
            $table->json('details')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('risk_measures', function (Blueprint $table) {
            $table->dropColumn(['details', 'completed_at']);
        });
    }
};
