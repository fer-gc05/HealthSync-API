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
        Schema::table('medical_record_audits', function (Blueprint $table) {
            // Eliminar la restricción existente
            $table->dropForeign(['medical_record_id']);
            
            // Agregar nueva restricción sin CASCADE para permitir auditoría de soft deletes
            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_record_audits', function (Blueprint $table) {
            // Eliminar la restricción restrict
            $table->dropForeign(['medical_record_id']);
            
            // Restaurar la restricción CASCADE original
            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('cascade');
        });
    }
};
