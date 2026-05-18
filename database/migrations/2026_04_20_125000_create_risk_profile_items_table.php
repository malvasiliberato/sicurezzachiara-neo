<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profile_items', function (Blueprint $table) {
            $table->id();
            $table->string('profileable_type');
            $table->unsignedBigInteger('profileable_id');
            $table->foreignId('risk_catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('uncovered');
            $table->string('priority', 20)->default('medium');
            $table->unsignedInteger('source_count')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['profileable_type', 'profileable_id', 'risk_catalog_item_id'], 'risk_profile_items_unique');
            $table->index(['profileable_type', 'profileable_id'], 'risk_profile_items_profileable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profile_items');
    }
};
