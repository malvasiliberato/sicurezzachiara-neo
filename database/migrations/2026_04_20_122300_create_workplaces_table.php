<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
            $table->foreignId('workplace_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status']);
            $table->unique(['company_site_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workplaces');
    }
};
