<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_job_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_role_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('assigned_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['worker_id', 'job_role_id']);
            $table->index(['worker_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_job_role_assignments');
    }
};
