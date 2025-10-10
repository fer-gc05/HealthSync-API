<?php

namespace App\Jobs;

use App\Mail\EmailVerificationMail;
use App\Models\EmailVerification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailVerificationJob implements ShouldQueue
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
            // Crear código de verificación.
            $verification = EmailVerification::createVerification($this->email);
            
            // Enviar email
            Mail::to($this->email)->send(
                new EmailVerificationMail(
                    $verification->code,
                    $this->userName
                )
            );

            Log::info('Email verification sent', [
                'email' => $this->email,
                'code' => $verification->code
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending email verification', [
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
