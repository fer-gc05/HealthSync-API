<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Jobs\SendEmailVerificationJob;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(protected User $user){}

    /**
     * Registro de usuario
     * @unauthenticated
     * */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->user->create($request->validated());
            $token = JWTAuth::fromUser($user);

            $user->assignRole('patient');

            // Enviar email de verificación
            SendEmailVerificationJob::dispatch($user->email, $user->name);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. An email verification code has been sent to your email.',
                'token' => $token,
                'email_verification_required' => true
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User registration failed',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Inicio de sesion
     * @unauthenticated

     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();
            if(!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'role' => JWTAuth::user()->getRoleNames()->first(),
                'token' => $token,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function me()
    {
        try {
            $user = JWTAuth::user()->load(['patient', 'medicalStaff', 'roles']);
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'user' => $user,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User retrieval failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cerrar sesión de usuario
     */
    public function logout()
    {
        try {
            Auth::logout();
            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar token de acceso
     */
    public function refresh()
    {
        try {
            if (!$token = JWTAuth::getToken()){
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, token not provided',
                ], Response::HTTP_UNAUTHORIZED);
            }
            $token = JWTAuth::refresh($token);
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'token' => $token,
            ], Response::HTTP_OK);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }
}
