<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_profile_item_reviews', function (Blueprint $table) {
            $table->string('follow_up_outcome_status')->nullable()->after('follow_up_due_at');
            $table->text('follow_up_outcome_notes')->nullable()->after('follow_up_outcome_status');
            $table->timestamp('follow_up_outcome_recorded_at')->nullable()->after('follow_up_outcome_notes');
        });
    }

    public function down(): void
    {
        Schema::table('risk_profile_item_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'follow_up_outcome_status',
                'follow_up_outcome_notes',
                'follow_up_outcome_recorded_at',
            ]);
        });
    }
};
