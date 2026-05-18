<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_measures', function (Blueprint $table) {
            $table->id();
            $table->string('profileable_type');
            $table->unsignedBigInteger('profileable_id');
            $table->foreignId('risk_catalog_item_id')->constrained()->cascadeOnDelete();
            $table->string('family', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 50);
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['profileable_type', 'profileable_id'], 'risk_measures_profileable_index');
            $table->index(['profileable_type', 'profileable_id', 'risk_catalog_item_id'], 'risk_measures_profileable_risk_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_measures');
    }
};
