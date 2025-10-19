<?php

namespace App\Http\Controllers\Calendar;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Google Calendar
 *
 * Este controlador maneja la integración con Google Calendar para sincronizar
 * citas médicas virtuales. Permite autenticación OAuth, creación de eventos,
 * actualización y eliminación de citas en el calendario de Google.
 *
 * @package App\Http\Controllers\Calendar
 * @author SaludOne Development Team
 * @version 1.0.0
 */
class GoogleCalendarController extends Controller
{
    /**
     * Constructor del controlador
     *
     * Inyecta el servicio de Google Calendar para manejar la integración
     * con la API de Google Calendar.
     *
     * @param GoogleCalendarService $calendarService Servicio de Google Calendar
     */
    public function __construct(protected GoogleCalendarService $calendarService)
    {}

    /**
     * Redirigir a Google para autenticación OAuth
     *
     * Este endpoint inicia el proceso de autenticación OAuth con Google Calendar.
     * Redirige al usuario a Google para autorizar el acceso a su calendario.
     *
     * @return \Illuminate\Http\RedirectResponse Redirección a Google OAuth
     *
     * @api {get} /api/calendar/auth/google Iniciar autenticación con Google
     * @apiName RedirectToGoogle
     * @apiGroup GoogleCalendar
     * @apiPermission public
     *
     * @apiSuccess {Redirect} 302 Redirección a Google OAuth
     *
     * @apiExample Ejemplo de uso:
     * GET /api/calendar/auth/google
     */
    public function redirectToGoogle()
    {
        // Crear un nuevo cliente para autenticación (sin renovación automática)
        $client = new \Google\Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
        $client->setAccessType('offline'); // Para obtener refresh_token
        $client->setPrompt('consent'); // Para forzar la obtención de refresh_token

        $authUrl = $client->createAuthUrl();

        // Redirigir automáticamente a Google
        return redirect()->away($authUrl);
    }

    /**
     * Manejar callback de Google OAuth
     *
     * Este endpoint procesa la respuesta de Google después de la autenticación OAuth.
     * Intercambia el código de autorización por un token de acceso y lo almacena
     * para futuras operaciones con Google Calendar.
     *
     * @param Request $request Request con el código de autorización de Google
     * @return JsonResponse Respuesta JSON con el estado de la autenticación
     *
     * @api {get} /api/calendar/auth/google/callback Callback de Google OAuth
     * @apiName HandleGoogleCallback
     * @apiGroup GoogleCalendar
     * @apiPermission public
     *
     * @apiParam {String} code Código de autorización de Google
     * @apiParam {String} [state] Estado opcional de la solicitud
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} message Mensaje de respuesta
     * @apiSuccess {String} status Estado de la conexión
     * @apiSuccess {String} type Tipo de token
     * @apiSuccess {Array} next_steps Pasos siguientes
     *
     * @apiExample Ejemplo de uso:
     * GET /api/calendar/auth/google/callback?code=4/0AX4XfWh...
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $this->calendarService->authenticate($request->get('code'));

            // Mostrar página de éxito
            return response()->json([
                'success' => true,
                'message' => 'Google Calendar authentication successful',
                'status' => 'connected',
                'type' => 'global_application_token',
                'next_steps' => [
                    'Google Calendar is now connected for the entire application',
                    'All users can now create calendar events',
                    'Use /api/calendar/events to manage events',
                    'Token is persisted globally for the application'
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in authentication: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar eventos del calendario de Google
     *
     * Este endpoint permite consultar los eventos existentes en el calendario
     * de Google conectado. Útil para verificar la sincronización y
     * consultar eventos creados por el sistema.
     *
     * @return JsonResponse Respuesta JSON con los eventos del calendario
     *
     * @api {get} /api/calendar/events Obtener eventos del calendario
     * @apiName ShowEvents
     * @apiGroup GoogleCalendar
     * @apiPermission authenticated
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de eventos del calendario
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/calendar/events
     */
    public function showEvents()
    {
        try {
            $events = $this->calendarService->listEvents('primary');

            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Events obtained successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting events: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear evento en el calendario de Google
     *
     * Este endpoint permite crear nuevos eventos en el calendario de Google.
     * Útil para sincronizar citas médicas virtuales con Google Calendar.
     *
     * @param Request $request Request con datos del evento
     * @return JsonResponse Respuesta JSON con el evento creado
     *
     * @api {post} /api/calendar/events Crear evento en calendario
     * @apiName StoreEvent
     * @apiGroup GoogleCalendar
     * @apiPermission authenticated
     *
     * @apiParam {String} summary Título del evento (requerido)
     * @apiParam {String} description Descripción del evento
     * @apiParam {String} start Fecha y hora de inicio (formato: Y-m-d H:i:s)
     * @apiParam {String} end Fecha y hora de fin (formato: Y-m-d H:i:s)
     * @apiParam {String} [location] Ubicación del evento
     * @apiParam {Array} [attendees] Lista de asistentes (emails)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos del evento creado
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * POST /api/calendar/events
     * {
     *   "summary": "Consulta médica virtual",
     *   "description": "Consulta con Dr. Juan Pérez",
     *   "start": "2024-01-15 10:00:00",
     *   "end": "2024-01-15 11:00:00",
     *   "location": "Google Meet"
     * }
     */
    public function storeEvent(Request $request)
    {
        try {
            $eventData = $request->only(['summary', 'description', 'start', 'end', 'attendees', 'virtual', 'location']);

            $event = $this->calendarService->createEvent($eventData);

            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => 'Event created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating event: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar evento en el calendario de Google
     *
     * Este endpoint permite actualizar un evento existente en el calendario de Google.
     * Útil para modificar citas médicas que ya están sincronizadas.
     *
     * @param Request $request Request con datos actualizados del evento
     * @param string $eventId ID del evento en Google Calendar
     * @return JsonResponse Respuesta JSON con el evento actualizado
     *
     * @api {put} /api/calendar/events/{eventId} Actualizar evento en calendario
     * @apiName UpdateEvent
     * @apiGroup GoogleCalendar
     * @apiPermission authenticated
     *
     * @apiParam {String} eventId ID del evento en Google Calendar
     * @apiParam {String} [summary] Nuevo título del evento
     * @apiParam {String} [description] Nueva descripción del evento
     * @apiParam {String} [start] Nueva fecha y hora de inicio
     * @apiParam {String} [end] Nueva fecha y hora de fin
     * @apiParam {String} [location] Nueva ubicación del evento
     * @apiParam {Array} [attendees] Nueva lista de asistentes
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos del evento actualizado
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * PUT /api/calendar/events/abc123def456
     * {
     *   "summary": "Consulta médica reprogramada",
     *   "start": "2024-01-16 14:00:00",
     *   "end": "2024-01-16 15:00:00"
     * }
     */
    public function updateEvent(Request $request, string $eventId)
    {
        try {
            $eventData = $request->only(['summary', 'description', 'start', 'end', 'attendees']);

            $event = $this->calendarService->updateEvent($eventId, $eventData);

            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => 'Event updated successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating event: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar evento del calendario de Google
     *
     * Este endpoint permite eliminar un evento del calendario de Google.
     * Útil para cancelar citas médicas que ya están sincronizadas.
     *
     * @param string $eventId ID del evento en Google Calendar
     * @return JsonResponse Respuesta JSON confirmando la eliminación
     *
     * @api {delete} /api/calendar/events/{eventId} Eliminar evento del calendario
     * @apiName DestroyEvent
     * @apiGroup GoogleCalendar
     * @apiPermission authenticated
     *
     * @apiParam {String} eventId ID del evento en Google Calendar
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * DELETE /api/calendar/events/abc123def456
     */
    public function destroyEvent(string $eventId)
    {
        try {
            $this->calendarService->deleteEvent($eventId);

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting event: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
