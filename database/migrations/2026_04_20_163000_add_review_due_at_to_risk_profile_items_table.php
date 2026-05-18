<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->date('review_due_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->dropColumn('review_due_at');
        });
    }
};
