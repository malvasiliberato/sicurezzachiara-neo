<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_source_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('sourceable_type');
            $table->unsignedBigInteger('sourceable_id');
            $table->string('relevance', 20)->default('primary');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['risk_catalog_item_id', 'sourceable_type', 'sourceable_id'], 'risk_source_links_unique');
            $table->index(['sourceable_type', 'sourceable_id'], 'risk_source_links_sourceable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_source_links');
    }
};
