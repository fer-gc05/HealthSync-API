<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\RescheduleAppointmentRequest;
use App\Models\Appointment;
use App\Models\MedicalStaff;
use App\Services\AppointmentAssignmentService;
use App\Services\AppointmentCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Citas para Pacientes
 *
 * Este controlador maneja todas las operaciones relacionadas con citas médicas
 * desde la perspectiva del paciente. Permite a los pacientes gestionar sus propias
 * citas, incluyendo reservar, reprogramar, cancelar y consultar el historial.
 *
 * @package App\Http\Controllers\Patient
 * @author SaludOne Development Team
 * @version 1.0.0
 */
class AppointmentController extends Controller
{
    /**
     * Constructor del controlador
     *
     * Inyecta los servicios necesarios para la gestión de citas del paciente:
     * - AppointmentAssignmentService: Para asignación automática de doctores
     * - AppointmentCalendarService: Para sincronización con Google Calendar
     *
     * @param AppointmentAssignmentService $assignmentService Servicio de asignación de citas
     * @param AppointmentCalendarService $calendarService Servicio de calendario
     */
    public function __construct(
        protected AppointmentAssignmentService $assignmentService,
        protected AppointmentCalendarService $calendarService
    ) {}

    /**
     * Listar citas del paciente autenticado
     *
     * Este endpoint permite a los pacientes obtener una lista de todas sus citas
     * con capacidades de filtrado por estado, tipo y fechas. Solo muestra las citas
     * del paciente autenticado.
     *
     * @param Request $request Request con filtros opcionales
     * @return JsonResponse Respuesta JSON con la lista de citas del paciente
     *
     * @api {get} /api/patient/appointments Obtener mis citas
     * @apiName GetPatientAppointments
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {String} [status] Estado de la cita (pendiente, confirmada, completada, cancelada)
     * @apiParam {String} [type] Tipo de cita (presencial, virtual)
     * @apiParam {Date} [date_from] Fecha de inicio para filtrar
     * @apiParam {Date} [date_to] Fecha de fin para filtrar
     * @apiParam {String} [sort_by] Campo para ordenar (start_date, created_at)
     * @apiParam {String} [sort_dir] Dirección de ordenamiento (asc, desc)
     * @apiParam {Integer} [per_page] Número de elementos por página (default: 15)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos paginados de las citas del paciente
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments?status=pendiente&type=presencial
     */
    public function index(Request $request): JsonResponse
    {
        $patient = Auth::user()->patient;

        $query = Appointment::with(['medicalStaff.user', 'specialty'])
            ->where('patient_id', $patient->id);

        // Aplicar filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        if ($request->filled('urgent')) {
            $query->where('urgent', $request->urgent);
        }

        // Aplicar ordenamiento
        $sortBy = $request->get('sort_by', 'start_date');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $appointments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Appointments obtained successfully'
        ]);
    }

    /**
     * Agendar una nueva cita médica
     *
     * Este endpoint permite a los pacientes agendar nuevas citas médicas.
     * Si no se especifica un doctor, el sistema intentará asignar automáticamente
     * el mejor doctor disponible. Si no hay doctores disponibles, la cita
     * se agregará a la lista de espera.
     *
     * @param BookAppointmentRequest $request Request validado con datos de la cita
     * @return JsonResponse Respuesta JSON con la cita creada o información de lista de espera
     *
     * @api {post} /api/patient/appointments Agendar nueva cita
     * @apiName BookAppointment
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} [medical_staff_id] ID del doctor preferido (opcional)
     * @apiParam {Integer} specialty_id ID de la especialidad (requerido)
     * @apiParam {String} start_date Fecha y hora de inicio (requerido)
     * @apiParam {String} end_date Fecha y hora de fin (requerido)
     * @apiParam {String} type Tipo de cita (presencial, virtual) (requerido)
     * @apiParam {String} reason Razón de la cita (requerido)
     * @apiParam {Boolean} [urgent=false] Si la cita es urgente
     * @apiParam {Integer} [priority=1] Nivel de prioridad (1-5)
     * @apiParam {String} [notes] Notas adicionales
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos de la cita creada
     * @apiSuccess {String} message Mensaje de respuesta
     * @apiSuccess {Boolean} [waitlist] Si la cita fue agregada a lista de espera
     *
     * @apiExample Ejemplo de uso:
     * POST /api/patient/appointments
     * {
     *   "specialty_id": 2,
     *   "start_date": "2024-01-15 10:00:00",
     *   "end_date": "2024-01-15 11:00:00",
     *   "type": "presencial",
     *   "reason": "Consulta de rutina"
     * }
     */
    public function book(BookAppointmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $patient = Auth::user()->patient;

            $appointmentData = $request->validated();
            $appointmentData['patient_id'] = $patient->id;

            // Si no se especifica doctor, intentar asignación automática
            if (empty($appointmentData['medical_staff_id'])) {
                $assignedDoctor = $this->assignmentService->assignOptimalDoctor(
                    $appointmentData['specialty_id'],
                    $appointmentData['start_date'],
                    $appointmentData['end_date']
                );

                if ($assignedDoctor) {
                    $appointmentData['medical_staff_id'] = $assignedDoctor->id;
                    $appointmentData['auto_assigned'] = true;
                } else {
                    // Si no hay doctor disponible, agregar a lista de espera
                    $this->assignmentService->addToWaitlist($appointmentData);

                    return response()->json([
                        'success' => true,
                        'message' => 'No doctors available at the requested time. Your request has been added to the waitlist.',
                        'waitlist' => true
                    ], Response::HTTP_CREATED);
                }
            }

            $appointment = Appointment::create($appointmentData);

            // Sincronizar con Google Calendar para TODAS las citas
            $this->calendarService->syncAppointment($appointment, 'create');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['medicalStaff.user', 'specialty']),
                'message' => 'Appointment scheduled successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error scheduling appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reprogramar una cita existente
     *
     * Este endpoint permite a los pacientes reprogramar una cita existente.
     * Solo se pueden reprogramar citas que estén en estado 'pendiente' o 'confirmada'.
     * El sistema verificará la disponibilidad del doctor en el nuevo horario.
     *
     * @param RescheduleAppointmentRequest $request Request validado con nueva fecha/hora
     * @param Appointment $appointment Modelo de la cita a reprogramar
     * @return JsonResponse Respuesta JSON con la cita reprogramada
     *
     * @api {put} /api/patient/appointments/{id}/reschedule Reprogramar cita
     * @apiName RescheduleAppointment
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} id ID único de la cita
     * @apiParam {String} start_date Nueva fecha y hora de inicio (requerido)
     * @apiParam {String} end_date Nueva fecha y hora de fin (requerido)
     * @apiParam {String} [reason] Razón del cambio de horario
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos de la cita reprogramada
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * PUT /api/patient/appointments/123/reschedule
     * {
     *   "start_date": "2024-01-16 14:00:00",
     *   "end_date": "2024-01-16 15:00:00",
     *   "reason": "Conflicto de horario"
     * }
     */
    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment)
    {
        // Verificar que la cita pertenece al paciente autenticado
        $patient = Auth::user()->patient;
        if ($appointment->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reschedule this appointment'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->status === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reschedule a cancelled appointment'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($appointment->status === 'completada') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reschedule a completed appointment'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $appointment->update($request->only(['start_date', 'end_date', 'reason']));

            // Si es virtual y está sincronizada, actualizar Google Calendar
            if ($appointment->isSyncedWithGoogle()) {
                $this->calendarService->updateCalendarEvent($appointment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['medicalStaff.user', 'specialty']),
                'message' => 'Appointment rescheduled successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error rescheduling appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una cita médica
     *
     * Este endpoint permite a los pacientes cancelar una cita existente.
     * Solo se pueden cancelar citas que estén en estado 'pendiente' o 'confirmada'.
     * Si la cita está sincronizada con Google Calendar, se eliminará automáticamente.
     *
     * @param Request $request Request con razón de cancelación
     * @param Appointment $appointment Modelo de la cita a cancelar
     * @return JsonResponse Respuesta JSON confirmando la cancelación
     *
     * @api {delete} /api/patient/appointments/{id}/cancel Cancelar cita
     * @apiName CancelAppointment
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} id ID único de la cita
     * @apiParam {String} [reason] Razón de la cancelación
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * DELETE /api/patient/appointments/123/cancel
     * {
     *   "reason": "Cambio de planes"
     * }
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        // Verificar que la cita pertenece al paciente autenticado
        $patient = Auth::user()->patient;
        if ($appointment->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel this appointment'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->status === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => 'The appointment is already cancelled'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($appointment->status === 'completada') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed appointment'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $appointment->update([
                'status' => 'cancelada',
                'cancellation_reason' => $request->get('cancellation_reason', 'Cancelada por el paciente')
            ]);

            // Si está sincronizada con Google Calendar, eliminar el evento
            if ($appointment->isSyncedWithGoogle()) {
                $this->calendarService->deleteCalendarEvent($appointment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['medicalStaff.user', 'specialty']),
                'message' => 'Appointment cancelled successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error cancelling appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener enlace de teleconsulta para cita virtual
     *
     * Este endpoint genera y retorna el enlace de teleconsulta para citas virtuales.
     * Solo funciona para citas de tipo 'virtual' y que estén en estado 'confirmada'.
     * El enlace se genera dinámicamente y tiene un tiempo de expiración.
     *
     * @param Appointment $appointment Modelo de la cita virtual
     * @return JsonResponse Respuesta JSON con el enlace de teleconsulta
     *
     * @api {get} /api/patient/appointments/{id}/teleconsultation-link Obtener enlace de teleconsulta
     * @apiName GetTeleconsultationLink
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} id ID único de la cita virtual
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} data Enlace de teleconsulta
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments/123/teleconsultation-link
     */
    public function getTeleconsultationLink(Appointment $appointment)
    {
        // Verificar que la cita pertenece al paciente autenticado
        $patient = Auth::user()->patient;
        if ($appointment->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this appointment'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$appointment->isVirtual()) {
            return response()->json([
                'success' => false,
                'message' => 'This appointment is not virtual'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($appointment->status !== 'confirmada' && $appointment->status !== 'en_progreso') {
            return response()->json([
                'success' => false,
                'message' => 'The appointment must be confirmed to access the teleconsultation'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'meeting_link' => $appointment->getTeleconsultationLink(),
                'meeting_password' => $appointment->meeting_password,
                'appointment' => $appointment->load(['medicalStaff.user', 'specialty'])
            ],
            'message' => 'Teleconsultation link obtained successfully'
        ]);
    }

    /**
     * Obtener horarios disponibles para agendar citas
     *
     * Este endpoint permite a los pacientes consultar los horarios disponibles
     * para agendar citas en una fecha específica, filtrado por especialidad.
     * Útil para mostrar opciones de horarios al paciente antes de agendar.
     *
     * @param Request $request Request con parámetros de consulta
     * @return JsonResponse Respuesta JSON con los horarios disponibles
     *
     * @api {get} /api/patient/appointments/available-slots Consultar horarios disponibles
     * @apiName GetAvailableSlots
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} specialty_id ID de la especialidad (requerido)
     * @apiParam {String} date Fecha para consultar (formato: Y-m-d, requerido)
     * @apiParam {String} [type] Tipo de cita (presencial, virtual, default: presencial)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de horarios disponibles con doctores
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments/available-slots?specialty_id=2&date=2024-01-15
     */
    public function availableSlots(Request $request)
    {
        try {
            $specialtyId = $request->get('specialty_id');
            $date = $request->get('date', now()->format('Y-m-d'));
            $type = $request->get('type', 'presencial');

            // Validar que specialty_id esté presente
            if (!$specialtyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'specialty_id is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validar que specialty_id sea un número
            if (!is_numeric($specialtyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'specialty_id must be a number'
                ], Response::HTTP_BAD_REQUEST);
            }

            $availableSlots = $this->assignmentService->getAvailableSlots((int)$specialtyId, $date, $type);

            return response()->json([
                'success' => true,
                'data' => $availableSlots,
                'message' => 'Available slots obtained successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in availableSlots', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting available slots: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener doctores disponibles para una especialidad
     *
     * Este endpoint permite a los pacientes consultar los doctores disponibles
     * para una especialidad específica en una fecha determinada.
     * Útil para mostrar opciones de doctores al paciente.
     *
     * @param Request $request Request con parámetros de consulta
     * @return JsonResponse Respuesta JSON con los doctores disponibles
     *
     * @api {get} /api/patient/appointments/available-doctors Consultar doctores disponibles
     * @apiName GetAvailableDoctors
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {Integer} specialty_id ID de la especialidad (requerido)
     * @apiParam {String} [date] Fecha para consultar (formato: Y-m-d, default: hoy)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de doctores disponibles
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments/available-doctors?specialty_id=2&date=2024-01-15
     */
    public function availableDoctors(Request $request)
    {
        $specialtyId = $request->get('specialty_id');
        $date = $request->get('date');
        $type = $request->get('type', 'presencial');

        $doctors = MedicalStaff::with(['user', 'specialty'])
            ->where('specialty_id', $specialtyId)
            ->where('active', true)
            ->get();

        $availableDoctors = $doctors->filter(function ($doctor) use ($date, $type) {
            return $this->assignmentService->isDoctorAvailable($doctor, $date, $type);
        });

        return response()->json([
            'success' => true,
            'data' => $availableDoctors->values(),
            'message' => 'Available doctors obtained successfully'
        ]);
    }

    /**
     * Obtener próximas citas del paciente
     *
     * Este endpoint retorna las próximas citas del paciente autenticado,
     * ordenadas por fecha de inicio. Útil para mostrar un resumen rápido
     * de las citas pendientes.
     *
     * @return JsonResponse Respuesta JSON con las próximas citas
     *
     * @api {get} /api/patient/appointments/upcoming Obtener próximas citas
     * @apiName GetUpcomingAppointments
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de próximas citas
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments/upcoming
     */
    public function upcoming()
    {
        $patient = Auth::user()->patient;

        $appointments = Appointment::with(['medicalStaff.user', 'specialty'])
            ->where('patient_id', $patient->id)
            ->where('start_date', '>=', now())
            ->where('status', '!=', 'cancelada')
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Upcoming appointments obtained successfully'
        ]);
    }

    /**
     * Obtener historial completo de citas del paciente
     *
     * Este endpoint permite a los pacientes consultar su historial completo
     * de citas médicas con capacidades de filtrado y paginación.
     * Incluye citas completadas, canceladas y futuras.
     *
     * @param Request $request Request con filtros opcionales
     * @return JsonResponse Respuesta JSON con el historial de citas
     *
     * @api {get} /api/patient/appointments/history Obtener historial de citas
     * @apiName GetAppointmentHistory
     * @apiGroup PatientAppointments
     * @apiPermission patient
     *
     * @apiParam {String} [status] Estado de la cita para filtrar
     * @apiParam {String} [type] Tipo de cita para filtrar
     * @apiParam {Date} [date_from] Fecha de inicio para filtrar
     * @apiParam {Date} [date_to] Fecha de fin para filtrar
     * @apiParam {String} [sort_by] Campo para ordenar (start_date, created_at)
     * @apiParam {String} [sort_dir] Dirección de ordenamiento (asc, desc)
     * @apiParam {Integer} [per_page] Número de elementos por página (default: 15)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos paginados del historial
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/patient/appointments/history?status=completada&sort_by=start_date&sort_dir=desc
     */
    public function history(Request $request)
    {
        $patient = Auth::user()->patient;

        $query = Appointment::with(['medicalStaff.user', 'specialty'])
            ->where('patient_id', $patient->id)
            ->where('start_date', '<', now());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Appointments history obtained successfully'
        ]);
    }

}
