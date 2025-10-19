<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'SaludOne API',
            'version' => '1.0.0',
            'environment' => app()->environment(),
            'database' => $this->checkDatabase(),
            'google_calendar' => $this->checkGoogleCalendar()
        ]);
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'driver' => config('database.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Google Calendar service
     */
    private function checkGoogleCalendar(): array
    {
        try {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');

            return [
                'status' => $clientId && $clientSecret ? 'configured' : 'not_configured',
                'client_id' => $clientId ? 'set' : 'missing',
                'client_secret' => $clientSecret ? 'set' : 'missing'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}
