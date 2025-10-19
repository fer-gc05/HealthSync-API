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
        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_staff_id')->constrained('medical_staff')->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->date('specific_date')->nullable(); // Para horarios específicos en fechas concretas
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['medical_staff_id', 'day_of_week']);
            $table->index(['medical_staff_id', 'specific_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_availability');
    }
};
