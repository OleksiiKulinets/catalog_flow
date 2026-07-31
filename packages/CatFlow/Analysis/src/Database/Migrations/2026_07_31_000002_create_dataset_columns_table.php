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
        Schema::create('dataset_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_schema_id')->constrained()->cascadeOnDelete();
            $table->string('source_column');
            $table->string('canonical_field')->nullable();
            $table->string('data_type');
            $table->string('currency_code')->nullable();
            $table->string('example_value')->nullable();
            $table->float('confidence')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_columns');
    }
};
