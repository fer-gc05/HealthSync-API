<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            // Índice único que considera soft deletes
            $table->unique(['name', 'deleted_at'], 'specialties_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropUnique('specialties_name_unique');
        });
    }
};
