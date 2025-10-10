<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Crear una nueva instancia de trabajo.
     */
    public function __construct(public string $email, public string $userName)
    {
    }

    /**
     * Ejecutar el trabajo.
     */
    public function handle(): void
    {
        try {
            // Enviar email de bienvenida.
            Mail::to($this->email)->send(
                new WelcomeMail($this->userName, $this->email)
            );

            Log::info('Welcome email sent', [
                'email' => $this->email,
                'userName' => $this->userName
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending welcome email', [
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
