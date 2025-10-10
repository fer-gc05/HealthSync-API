<?php

namespace App\Console\Commands;

use App\Models\EmailVerification;
use Illuminate\Console\Command;

class CleanExpiredVerifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar códigos de verificación expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = EmailVerification::cleanExpired();
        
        $this->info("Se eliminaron {$deleted} códigos de verificación expirados.");
        
        return Command::SUCCESS;
    }
}
