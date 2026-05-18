<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profile_item_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_profile_item_id')->constrained()->cascadeOnDelete();
            $table->string('sourceable_type');
            $table->unsignedBigInteger('sourceable_id');
            $table->string('source_family', 50);
            $table->string('source_label');
            $table->string('relevance', 20)->default('primary');
            $table->timestamps();

            $table->unique(['risk_profile_item_id', 'sourceable_type', 'sourceable_id'], 'risk_profile_item_sources_unique');
            $table->index(['sourceable_type', 'sourceable_id'], 'risk_profile_item_sources_sourceable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profile_item_sources');
    }
};
