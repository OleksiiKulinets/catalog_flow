<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('openai');
            $table->string('model');
            $table->text('prompt');
            $table->string('status')->default('draft');
            $table->string('provider_job_id')->nullable()->index();
            $table->string('input_file_id')->nullable();
            $table->string('input_jsonl_path')->nullable();
            $table->string('output_file_id')->nullable();
            $table->string('error_file_id')->nullable();
            $table->unsignedInteger('request_total')->default(0);
            $table->unsignedInteger('request_completed')->default(0);
            $table->unsignedInteger('request_failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
