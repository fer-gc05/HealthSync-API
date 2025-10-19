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
        Schema::create('appointment_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            $table->foreignId('preferred_doctor_id')->nullable()->constrained('medical_staff')->onDelete('set null');
            $table->enum('type', ['presencial', 'virtual']);
            $table->text('reason');
            $table->boolean('urgent')->default(false);
            $table->integer('priority')->default(1);
            $table->integer('position')->default(1);
            $table->enum('status', ['waiting', 'assigned', 'cancelled'])->default('waiting');
            $table->timestamp('preferred_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['specialty_id', 'status', 'position']);
            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_waitlist');
    }
};
