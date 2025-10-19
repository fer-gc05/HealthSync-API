<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleCalendarService;

class RefreshGoogleCalendarToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Google Calendar token manually';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $calendarService = new GoogleCalendarService();

            // Intentar renovar el token
            $this->info('Attempting to refresh Google Calendar token...');

            // Simular una operación que requiera el token
            $calendarService->listEvents();

            $this->info('✅ Google Calendar token refreshed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Failed to refresh Google Calendar token: ' . $e->getMessage());
            $this->error('Please re-authenticate with Google Calendar.');
            return 1;
        }

        return 0;
    }
}
