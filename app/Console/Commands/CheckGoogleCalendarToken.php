<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGoogleCalendarToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:check-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Google Calendar token status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tokenPath = storage_path('app/google-calendar-token.json');

        if (!file_exists($tokenPath)) {
            $this->error('❌ No Google Calendar token found.');
            $this->info('Please authenticate with Google Calendar first.');
            return 1;
        }

        $token = json_decode(file_get_contents($tokenPath), true);

        $this->info('📋 Google Calendar Token Status:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Verificar si tiene refresh_token
        if (isset($token['refresh_token'])) {
            $this->info('✅ Refresh Token: Available');
        } else {
            $this->error('❌ Refresh Token: Not available');
            $this->warn('⚠️  Token will expire and cannot be automatically renewed.');
        }

        // Verificar expiración
        if (isset($token['created']) && isset($token['expires_in'])) {
            $expiresAt = $token['created'] + $token['expires_in'];
            $now = time();
            $timeLeft = $expiresAt - $now;

            if ($timeLeft > 0) {
                $hours = floor($timeLeft / 3600);
                $minutes = floor(($timeLeft % 3600) / 60);
                $this->info("⏰ Expires in: {$hours}h {$minutes}m");

                if ($timeLeft < 300) { // Menos de 5 minutos
                    $this->warn('⚠️  Token expires soon!');
                }
            } else {
                $this->error('❌ Token has expired!');
            }
        } else {
            $this->warn('⚠️  Token expiration info not available');
        }

        // Verificar scope
        if (isset($token['scope'])) {
            $this->info('🔐 Scope: ' . $token['scope']);
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }
}
