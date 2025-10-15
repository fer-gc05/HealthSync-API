<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('notifications', 'recipient_id')) {
                $table->unsignedBigInteger('recipient_id')->nullable()->after('sender_id');
            }
            if (!Schema::hasColumn('notifications', 'metadata')) {
                $table->json('metadata')->nullable()->after('message');
            }
            if (!Schema::hasColumn('notifications', 'appointment_id')) {
                $table->unsignedBigInteger('appointment_id')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('notifications', 'medical_record_id')) {
                $table->unsignedBigInteger('medical_record_id')->nullable()->after('appointment_id');
            }
            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->unsignedTinyInteger('priority')->nullable()->after('medical_record_id');
            }

            // Indexes
            $table->index('recipient_id');
            $table->index('type');
            $table->index('read_at');
            $table->index('appointment_id');
            $table->index('medical_record_id');

            // Foreign keys (soft constraints; set null on delete)
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->nullOnDelete();
            if (Schema::hasTable('appointments')) {
                $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            }
            if (Schema::hasTable('medical_records')) {
                $table->foreign('medical_record_id')->references('id')->on('medical_records')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop foreign keys if exist
            foreach (['sender_id', 'recipient_id', 'appointment_id', 'medical_record_id'] as $col) {
                $fkName = 'notifications_' . $col . '_foreign';
                if (Schema::hasColumn('notifications', $col)) {
                    try { $table->dropForeign($fkName); } catch (\Throwable $e) { /* ignore */ }
                }
            }

            // Drop indexes
            foreach (['recipient_id', 'type', 'read_at', 'appointment_id', 'medical_record_id'] as $col) {
                try { $table->dropIndex(['notifications_' . $col . '_index']); } catch (\Throwable $e) { /* ignore */ }
                try { $table->dropIndex([$col]); } catch (\Throwable $e) { /* ignore */ }
            }

            // Drop columns if they exist
            foreach (['sender_id', 'recipient_id', 'metadata', 'appointment_id', 'medical_record_id', 'priority'] as $col) {
                if (Schema::hasColumn('notifications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};


