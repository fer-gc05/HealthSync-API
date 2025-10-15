<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar columna enum `type` a VARCHAR(50) para soportar nuevos tipos
        DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
        // índice puede existir desde otra migración; ignorar si falla
    }

    public function down(): void
    {
        // Revertir a enum original si fuese necesario
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM('recordatorio_cita','nuevo_mensaje','resultado_lab') NOT NULL");
    }
};


