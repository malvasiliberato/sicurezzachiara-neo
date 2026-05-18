<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('risk_category_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_priority', 20)->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_catalog_items');
    }
};
