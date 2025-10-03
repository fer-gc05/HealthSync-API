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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('medical_staff_id')->constrained('medical_staff')->onDelete('cascade');
            $table->text('subjective')->nullable(); // Síntomas del paciente
            $table->text('objective')->nullable(); // Hallazgos del médico
            $table->text('assessment')->nullable(); // Evaluación/Diagnóstico
            $table->text('plan')->nullable(); // Plan de tratamiento
            $table->json('vital_signs')->nullable(); // Signos vitales
            $table->text('prescriptions')->nullable(); // Recetas médicas
            $table->text('recommendations')->nullable(); // Recomendaciones
            $table->string('file_url')->nullable(); // URL de archivos adjuntos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
