<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir a Google OAuth
     * @unauthenticated
     */
    public function redirect()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google OAuth redirect failed',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Manejar callback de Google OAuth
     * @unauthenticated
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Validar datos del usuario de Google
            if (!$googleUser->getEmail() || !$googleUser->getId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google user data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Buscar usuario existente por email
            $existingUser = User::where('email', $googleUser->getEmail())->first();

            // Obtener datos de Google de forma segura
            $googleId = $googleUser->getId();
            $googleToken = $googleUser->token ?? null;
            $googleRefreshToken = $googleUser->refreshToken ?? null;
            $googleExpiresIn = $googleUser->expiresIn ?? null;

            if ($existingUser) {
                // Usuario existente - vincular cuenta Google si no está vinculada
                if (!$existingUser->hasGoogleAccount()) {
                    $existingUser->linkGoogleAccount(
                        $googleId,
                        $googleToken,
                        $googleRefreshToken,
                        $googleExpiresIn ? Carbon::now()->addSeconds($googleExpiresIn) : null
                    );
                }
                
                $user = $existingUser;
            } else {
                // Usuario nuevo - crear cuenta
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                     // como la contrasena es nullable, no se necesita hacer hash
                    'google_id' => $googleId,
                    'google_token' => $googleToken,
                    'google_refresh_token' => $googleRefreshToken,
                    'google_token_expires_at' => $googleExpiresIn ? Carbon::now()->addSeconds($googleExpiresIn) : null,
                    'email_verified_at' => now(), // Email verificado por Google
                ]);

                // Asignar rol por defecto
                $user->assignRole('patient');
            }

            // Generar JWT token
            $token = JWTAuth::fromUser($user);

            // TODO: Redirigir al frontend con el token cuando esté disponible
            //$frontendUrl = env('FRONTEND_URL','');
            // $redirectUrl = $frontendUrl . '/auth/callback?token=' . $token . '&success=true';
            // return redirect($redirectUrl);

            // Temporal: Mostrar token para testing
            return response()->json([
                'success' => true,
                'message' => 'Google OAuth authentication successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'has_google_account' => $user->hasGoogleAccount(),
                    'role' => $user->getRoleNames()->first()
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed: ' . $e->getMessage());
            
            // TODO: Redirigir al frontend con error cuando esté disponible
            //$frontendUrl = env('FRONTEND_URL','');
            // $redirectUrl = $frontendUrl . '/auth/callback?success=false&error=' . urlencode($e->getMessage());
            // return redirect($redirectUrl);
            
            // Temporal: Mostrar error para testing
            return response()->json([
                'success' => false,
                'message' => 'Google OAuth authentication failed',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vincular cuenta Google a usuario existente
     */
    public function link(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user->hasGoogleAccount()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google account is already linked to this user'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Obtener código de autorización del request
            $authCode = $request->input('code');
            
            if (!$authCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Intercambiar código por token
            $googleUser = Socialite::driver('google')->user();
            
            // Obtener datos de Google de forma segura
            $googleId = $googleUser->getId();
            $googleToken = $googleUser->token ?? null;
            $googleRefreshToken = $googleUser->refreshToken ?? null;
            $googleExpiresIn = $googleUser->expiresIn ?? null;
            
            // Vincular cuenta Google
            $user->linkGoogleAccount(
                $googleId,
                $googleToken,
                $googleRefreshToken,
                $googleExpiresIn ? Carbon::now()->addSeconds($googleExpiresIn) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Google account linked successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Google account linking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to link Google account',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desvincular cuenta Google del usuario
     */
    public function unlink()
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasGoogleAccount()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Google account linked to this user'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user->unlinkGoogleAccount();

            return response()->json([
                'success' => true,
                'message' => 'Google account unlinked successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Google account unlinking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink Google account',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener estado de vinculación de Google
     */
    public function status()
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'success' => true,
                'has_google_account' => $user->hasGoogleAccount(),
                'google_token_expired' => $user->hasGoogleAccount() ? $user->isGoogleTokenExpired() : null
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Google account status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check Google account status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}