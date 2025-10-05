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
        Schema::create('medical_record_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['medical_record_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_record_files');
    }
};
