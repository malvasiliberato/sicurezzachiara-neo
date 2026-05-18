<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->foreignId('operational_owner_user_id')->nullable()->after('review_due_at')->constrained('users')->nullOnDelete();
            $table->string('follow_up_status', 50)->nullable()->after('operational_owner_user_id');
            $table->text('follow_up_notes')->nullable()->after('follow_up_status');
            $table->date('follow_up_due_at')->nullable()->after('follow_up_notes');
            $table->timestamp('taken_in_charge_at')->nullable()->after('follow_up_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operational_owner_user_id');
            $table->dropColumn([
                'follow_up_status',
                'follow_up_notes',
                'follow_up_due_at',
                'taken_in_charge_at',
            ]);
        });
    }
};
