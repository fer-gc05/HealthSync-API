<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario no está autenticado, continuar
        if (!$user) {
            return $next($request);
        }

        // Si el email no está verificado, devolver error
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Firts verify your email before accessing this functionality',
                'email_verification_required' => true,
                'email' => $user->email
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
