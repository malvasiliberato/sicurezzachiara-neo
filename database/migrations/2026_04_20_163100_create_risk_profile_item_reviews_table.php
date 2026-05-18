<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profile_item_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_profile_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('operational_status', 50);
            $table->string('consultant_decision', 50)->nullable();
            $table->string('final_priority', 50)->nullable();
            $table->text('consultant_notes')->nullable();
            $table->date('review_due_at')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profile_item_reviews');
    }
};
