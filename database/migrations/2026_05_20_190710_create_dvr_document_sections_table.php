<?php

use App\Models\DvrDocumentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvr_document_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvr_document_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 80);
            $table->string('title');
            $table->string('status', 32)->default(DvrDocumentSection::STATUS_NEEDS_REVIEW);
            $table->string('generation_mode', 32);
            $table->string('source_status', 32)->default(DvrDocumentSection::SOURCE_LIVE);
            $table->json('payload')->nullable();
            $table->text('manual_content')->nullable();
            $table->text('consultant_notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['dvr_document_id', 'section_key'], 'dvr_document_sections_unique_key');
            $table->index(['dvr_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvr_document_sections');
    }
};
