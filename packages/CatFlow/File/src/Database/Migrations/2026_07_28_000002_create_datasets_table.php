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
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type', 20);
            $table->string('source_format', 20);

            // Upload-specific — null for a dataset imported from Google Sheets.
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // Google Sheets-specific — null for a directly uploaded file.
            $table->string('external_url')->nullable();
            $table->string('spreadsheet_id')->nullable();
            $table->string('sheet_gid')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->unsignedInteger('rows_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};
