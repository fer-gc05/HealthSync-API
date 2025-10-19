<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', function () {
    return response()->json([
        'name' => 'SaludOne API - Portal de Coordinación de Citas y Teleasistencia',
        'description' => 'Sistema integral de gestión médica para clínicas y centros de salud',
        'version' => 'v1.0',
        'sector' => 'HealthTech',
        'project' => 'No Country - Web App',
        'laravel_version' => app()->version(),
        'core_features' => [
            'Gestión de citas presenciales y virtuales',
            'Historiales médicos electrónicos',
            'Sistema de teleconsulta integrado',
            'Recordatorios automáticos',
            'Integración con sistemas EHR (FHIR)'
        ],
        'status' => 'active',
        'health' => url('/api/health'),
        'ducumentation' => [
            'documentation ui' => url('/docs/v1/api'),
            'documentation json' => url('/docs/v1/openapi.json')
        ],
    ], Response::HTTP_OK);
});
