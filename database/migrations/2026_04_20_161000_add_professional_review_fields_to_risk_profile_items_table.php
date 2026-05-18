<?php

use App\Models\RiskProfileItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->string('final_priority')->nullable()->after('priority');
            $table->boolean('is_manual')->default(false)->after('source_count');
            $table->boolean('is_currently_derived')->default(true)->after('is_manual');
            $table->string('operational_status')->default(RiskProfileItem::OPERATIONAL_STATUS_ACTIVE)->after('is_currently_derived');
            $table->string('consultant_decision')->nullable()->after('operational_status');
            $table->text('consultant_notes')->nullable()->after('consultant_decision');
            $table->timestamp('reviewed_at')->nullable()->after('consultant_notes');
        });
    }

    public function down(): void
    {
        Schema::table('risk_profile_items', function (Blueprint $table) {
            $table->dropColumn([
                'final_priority',
                'is_manual',
                'is_currently_derived',
                'operational_status',
                'consultant_decision',
                'consultant_notes',
                'reviewed_at',
            ]);
        });
    }
};
