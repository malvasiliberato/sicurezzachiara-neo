<?php

use App\Models\DvrDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default(DvrDocument::STATUS_DRAFT);
            $table->unsignedInteger('version_number')->default(1);
            $table->string('title');
            $table->timestamp('generated_from_live_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('frozen_at')->nullable();
            $table->foreignId('frozen_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supersedes_document_id')->nullable()->constrained('dvr_documents')->nullOnDelete();
            $table->text('revision_reason')->nullable();
            $table->string('completeness_status', 32)->default(DvrDocument::COMPLETENESS_INCOMPLETE);
            $table->json('snapshot_payload')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'company_id', 'status']);
            $table->unique(['company_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvr_documents');
    }
};
