<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Exception as GoogleException;
use DateTime;
use DateTimeZone;
use DateInterval;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected $client;
    protected $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline'); // Para obtener refresh_token
        $this->client->setPrompt('consent'); // Para forzar la obtención de refresh_token
        $this->calendarService = new Calendar($this->client);
    }

    public function authenticate($code)
    {
        $this->client->authenticate($code);
        $token = $this->client->getAccessToken();

        // Guardar en archivo para persistencia global de la aplicación
        $this->saveTokenToFile($token);

        return $token;
    }

    /**
     * Guardar token en archivo para persistencia global
     */
    private function saveTokenToFile($token)
    {
        $tokenPath = storage_path('app/google-calendar-token.json');
        file_put_contents($tokenPath, json_encode($token));
    }

    /**
     * Verificar si el token necesita renovación
     */
    private function isTokenExpired($token)
    {
        if (!isset($token['created']) || !isset($token['expires_in'])) {
            return true;
        }

        $expiresAt = $token['created'] + $token['expires_in'];
        $now = time();

        // Renovar si queda menos de 5 minutos
        return ($expiresAt - $now) < 300;
    }

    /**
     * Renovar token automáticamente
     */
    private function refreshTokenIfNeeded()
    {
        $token = $this->getStoredToken();

        if (!$token) {
            // No hay token, no hacer nada (se manejará en el flujo de autenticación)
            return;
        }

        if ($this->isTokenExpired($token)) {
            if (isset($token['refresh_token'])) {
                $this->client->setAccessToken($token);

                if ($this->client->isAccessTokenExpired()) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);

                    if (isset($newToken['error'])) {
                        Log::error('Error refreshing Google Calendar token', $newToken);
                        throw new \Exception('Failed to refresh Google Calendar token: ' . $newToken['error']);
                    }

                    $this->saveTokenToFile($newToken);
                    $this->client->setAccessToken($newToken);
                }
            } else {
                // No hay refresh_token, no intentar renovar
                Log::warning('Google Calendar token expired and no refresh token available');
                return;
            }
        } else {
            $this->client->setAccessToken($token);
        }
    }

    /**
     * Obtener token almacenado
     */
    private function getStoredToken()
    {
        $tokenPath = storage_path('app/google-calendar-token.json');

        if (!file_exists($tokenPath)) {
            return null;
        }

        $token = json_decode(file_get_contents($tokenPath), true);
        return $token;
    }

    /**
     * Cargar token desde archivo
     */
    private function loadTokenFromFile()
    {
        $tokenPath = storage_path('app/google-calendar-token.json');
        if (file_exists($tokenPath)) {
            $token = json_decode(file_get_contents($tokenPath), true);
            return $token;
        }
        return null;
    }

    public function getClient()
    {
        // Solo renovar token si ya existe uno válido
        $token = $this->getStoredToken();
        if ($token && !$this->isTokenExpired($token)) {
            $this->client->setAccessToken($token);
        }

        return $this->client;
    }

    public function listEvents($calendarId = 'primary')
    {
        $this->getClient();
        $events = $this->calendarService->events->listEvents($calendarId);
        return $events->getItems();
    }

    public function createEvent($eventData, $calendarId = 'primary')
    {
        // Renovar token automáticamente si es necesario
        $this->refreshTokenIfNeeded();

        // Formatear datos del evento para Google Calendar
        $formattedEventData = [
            'summary' => $eventData['summary'] ?? 'Evento sin título',
            'description' => $eventData['description'] ?? '',
            'location' => $eventData['location'] ?? '',
        ];

        // Formatear fechas si están presentes
        if (isset($eventData['start'])) {
            $formattedEventData['start'] = [
                'dateTime' => $eventData['start'],
                'timeZone' => 'America/Bogota'
            ];
        }

        if (isset($eventData['end'])) {
            $formattedEventData['end'] = [
                'dateTime' => $eventData['end'],
                'timeZone' => 'America/Bogota'
            ];
        }

        // Agregar asistentes si están presentes
        if (isset($eventData['attendees']) && is_array($eventData['attendees'])) {
            $formattedEventData['attendees'] = array_map(function($email) {
                return ['email' => $email];
            }, $eventData['attendees']);
        }

        $event = new Event($formattedEventData);

        // Configurar Google Meet si es un evento virtual
        $isVirtual = isset($eventData['virtual']) && $eventData['virtual'] === true;
        $isMedicalVirtual = isset($eventData['summary']) &&
            (strpos(strtolower($eventData['summary']), 'cita') !== false ||
             strpos(strtolower($eventData['summary']), 'consulta') !== false ||
             strpos(strtolower($eventData['summary']), 'médica') !== false) &&
            isset($eventData['description']) &&
            strpos(strtolower($eventData['description']), 'tipo: virtual') !== false;

        if ($isVirtual || $isMedicalVirtual) {
            $this->addGoogleMeetToEvent($event);
        }

        // Insertar evento con conferenceDataVersion para generar Google Meet
        $optParams = [];
        if ($event->getConferenceData()) {
            $optParams['conferenceDataVersion'] = 1;
        }

        $event = $this->calendarService->events->insert($calendarId, $event, $optParams);

        return $event;
    }

    /**
     * Agregar Google Meet a un evento
     */
    private function addGoogleMeetToEvent(Event $event)
    {
        $conferenceData = new \Google\Service\Calendar\ConferenceData();
        $conferenceSolutionKey = new \Google\Service\Calendar\ConferenceSolutionKey();
        $conferenceSolutionKey->setType('hangoutsMeet');

        $conferenceData->setCreateRequest(new \Google\Service\Calendar\CreateConferenceRequest());
        $conferenceData->getCreateRequest()->setRequestId(uniqid());
        $conferenceData->getCreateRequest()->setConferenceSolutionKey($conferenceSolutionKey);

        $event->setConferenceData($conferenceData);
    }

    public function updateEvent($eventId, $eventData, $calendarId = 'primary')
    {
        $this->getClient();
        $event = $this->calendarService->events->get($calendarId, $eventId);

        // Actualizar campos específicos
        if (isset($eventData['summary'])) {
            $event->setSummary($eventData['summary']);
        }

        if (isset($eventData['start'])) {
            $event->getStart()->setDateTime($eventData['start']['dateTime']);
        }

        if (isset($eventData['end'])) {
            $event->getEnd()->setDateTime($eventData['end']['dateTime']);
        }

        $updatedEvent = $this->calendarService->events->update($calendarId, $eventId, $event);
        return $updatedEvent;
    }

    public function deleteEvent($eventId, $calendarId = 'primary')
    {
        $this->getClient();
        $this->calendarService->events->delete($calendarId, $eventId);
        return true;
    }

    public function listUpcomingEvents($maxResults = 10, $calendarId = 'primary')
    {
        $this->getClient();
        $now = gmdate('c');
        $tomorrow = (new DateTime('now', new DateTimeZone('UTC')))->add(new DateInterval('P5D'))->format('c');

        $optParams = [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $now,
            'timeMax' => $tomorrow,
        ];

        $events = $this->calendarService->events->listEvents($calendarId, $optParams);
        return $events->getItems();
    }

}
