<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Models\EmailVerification;
use App\Models\User;
use App\Jobs\SendEmailVerificationJob;
use App\Jobs\SendWelcomeEmailJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    /**
     * Verificar código de email
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $verification = EmailVerification::where('email', $request->email)
                ->where('code', $request->code)
                ->valid()
                ->first();

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code and email are not valid'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Marcar email como verificado
            $user = User::where('email', $request->email)->first();
            $user->markEmailAsVerified();
            
            // Marcar código como usado
            $verification->markAsUsed();

            // Enviar email de bienvenida después de verificar
            SendWelcomeEmailJob::dispatch($user->email, $user->name);

            Log::info('Email verified successfully', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully. A welcome email has been sent.'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error verifying email', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reenviar código de verificación
     */
    public function resend(ResendVerificationRequest $request): JsonResponse
    {
        $key = 'resend-verification:' . $request->email;

        // Rate limiting: máximo 3 intentos por hora
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Try again in {$seconds} seconds."
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $user = User::where('email', $request->email)->first();

            // Verificar si el email ya está verificado
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already verified'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Enviar nuevo código de verificación
            SendEmailVerificationJob::dispatch($request->email, $user->name);

            // Registrar intento
            RateLimiter::hit($key, 3600); // 1 hora

            Log::info('Verification code reenviado', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification code reenviado exitosamente'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error resending verification code', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener estado de verificación del usuario autenticado
     */
    public function status(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'success' => true,
                'verified' => $user->hasVerifiedEmail(),
                'email' => $user->email
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error getting verification status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
