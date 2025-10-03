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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('medical_staff_id')->constrained('medical_staff')->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->enum('type', ['presencial', 'virtual']);
            $table->enum('status', ['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])->default('programada');
            $table->text('reason')->nullable();
            $table->boolean('urgent')->default(false);
            $table->integer('priority')->default(3); // 1-5 scale
            $table->string('video_url')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->enum('attendance_status', ['asistio', 'no_asistio', 'retraso', 'inconveniente'])->nullable();
            $table->text('attendance_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
