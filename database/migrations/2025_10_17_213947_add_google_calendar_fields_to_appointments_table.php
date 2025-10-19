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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('video_url');
            $table->string('meeting_link')->nullable()->after('google_event_id');
            $table->string('meeting_password')->nullable()->after('meeting_link');
            $table->boolean('calendar_synced')->default(false)->after('meeting_password');
            $table->boolean('auto_assigned')->default(false)->after('calendar_synced');
            $table->integer('waitlist_position')->nullable()->after('auto_assigned');
            $table->json('google_event_data')->nullable()->after('waitlist_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'google_event_id',
                'meeting_link',
                'meeting_password',
                'calendar_synced',
                'auto_assigned',
                'waitlist_position',
                'google_event_data'
            ]);
        });
    }
};
