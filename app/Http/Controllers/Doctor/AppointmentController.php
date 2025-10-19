<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\ScheduleAppointmentRequest;
use App\Http\Requests\Doctor\UpdateAvailabilityRequest;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Services\AppointmentCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Citas para Doctores
 *
 * Este controlador maneja todas las operaciones relacionadas con citas médicas
 * desde la perspectiva del doctor. Permite a los doctores gestionar sus citas,
 * actualizar disponibilidad, confirmar citas y acceder a información de pacientes.
 *
 * @package App\Http\Controllers\Doctor
 * @author SaludOne Development Team
 * @version 1.0.0
 */
class AppointmentController extends Controller
{
    /**
     * Constructor del controlador
     *
     * Inyecta el servicio de calendario para sincronización con Google Calendar
     * en citas virtuales y gestión de disponibilidad.
     *
     * @param AppointmentCalendarService $calendarService Servicio de calendario
     */
    public function __construct(protected AppointmentCalendarService $calendarService)
    {}

    /**
     * Listar citas del doctor autenticado
     *
     * Este endpoint permite a los doctores obtener una lista de todas sus citas
     * con capacidades de filtrado por estado, tipo, fechas y urgencia.
     * Solo muestra las citas del doctor autenticado.
     *
     * @param Request $request Request con filtros opcionales
     * @return JsonResponse Respuesta JSON con la lista de citas del doctor
     *
     * @api {get} /api/doctor/appointments Obtener mis citas
     * @apiName GetDoctorAppointments
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {String} [status] Estado de la cita (pendiente, confirmada, completada, cancelada)
     * @apiParam {String} [type] Tipo de cita (presencial, virtual)
     * @apiParam {Date} [date_from] Fecha de inicio para filtrar
     * @apiParam {Date} [date_to] Fecha de fin para filtrar
     * @apiParam {Boolean} [urgent] Filtrar por citas urgentes
     * @apiParam {String} [sort_by] Campo para ordenar (start_date, created_at)
     * @apiParam {String} [sort_dir] Dirección de ordenamiento (asc, desc)
     * @apiParam {Integer} [per_page] Número de elementos por página (default: 15)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos paginados de las citas del doctor
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/doctor/appointments?status=pendiente&type=presencial
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->medicalStaff;

        $query = Appointment::with(['patient.user', 'specialty'])
            ->where('medical_staff_id', $doctor->id);

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
     * Programar una nueva cita médica
     *
     * Este endpoint permite a los doctores programar nuevas citas médicas
     * para sus pacientes. El doctor puede especificar todos los detalles
     * de la cita incluyendo paciente, fecha, hora y tipo de consulta.
     *
     * @param ScheduleAppointmentRequest $request Request validado con datos de la cita
     * @return JsonResponse Respuesta JSON con la cita programada
     *
     * @api {post} /api/doctor/appointments Programar nueva cita
     * @apiName ScheduleAppointment
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {Integer} patient_id ID del paciente (requerido)
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
     * @apiSuccess {Object} data Datos de la cita programada
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * POST /api/doctor/appointments
     * {
     *   "patient_id": 1,
     *   "specialty_id": 2,
     *   "start_date": "2024-01-15 10:00:00",
     *   "end_date": "2024-01-15 11:00:00",
     *   "type": "presencial",
     *   "reason": "Consulta de seguimiento"
     * }
     */
    public function schedule(ScheduleAppointmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $doctor = Auth::user()->medicalStaff;

            $appointmentData = $request->validated();
            $appointmentData['medical_staff_id'] = $doctor->id;

            $appointment = Appointment::create($appointmentData);

            // Sincronizar con Google Calendar si es virtual
            if ($appointment->isVirtual()) {
                $this->calendarService->syncAppointment($appointment, 'create');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['patient.user', 'specialty']),
                'message' => 'Appointment scheduled successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al programar la cita: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar una cita existente
     *
     * Este endpoint permite a los doctores actualizar los detalles de una cita existente.
     * Solo pueden actualizar citas que les pertenecen. Si la cita está sincronizada
     * con Google Calendar, se actualizará automáticamente.
     *
     * @param Request $request Request con datos a actualizar
     * @param Appointment $appointment Modelo de la cita a actualizar
     * @return JsonResponse Respuesta JSON con la cita actualizada
     *
     * @api {put} /api/doctor/appointments/{id} Actualizar cita
     * @apiName UpdateAppointment
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {Integer} id ID único de la cita
     * @apiParam {String} [start_date] Nueva fecha y hora de inicio
     * @apiParam {String} [end_date] Nueva fecha y hora de fin
     * @apiParam {String} [type] Tipo de cita (presencial, virtual)
     * @apiParam {String} [status] Estado de la cita
     * @apiParam {String} [reason] Razón de la cita
     * @apiParam {Boolean} [urgent] Si la cita es urgente
     * @apiParam {Integer} [priority] Nivel de prioridad
     * @apiParam {String} [notes] Notas adicionales
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos actualizados de la cita
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * PUT /api/doctor/appointments/123
     * {
     *   "status": "confirmada",
     *   "notes": "Paciente confirmó asistencia"
     * }
     */
    public function show(Appointment $appointment)
    {
        // Verificar que la cita pertenece al doctor autenticado
        $doctor = Auth::user()->medicalStaff;
        if ($appointment->medical_staff_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this appointment'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient.user', 'specialty']),
            'message' => 'Appointment details retrieved successfully'
        ]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        // Verificar que la cita pertenece al doctor autenticado
        $doctor = Auth::user()->medicalStaff;
        if ($appointment->medical_staff_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this appointment'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            DB::beginTransaction();

            $appointment->update($request->only([
                'start_date',
                'end_date',
                'status',
                'reason',
                'attendance_status',
                'attendance_notes'
            ]));

            // Sincronizar cambios con Google Calendar
            if ($appointment->isSyncedWithGoogle()) {
                $this->calendarService->updateCalendarEvent($appointment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['patient.user', 'specialty']),
                'message' => 'Appointment updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Iniciar sesión de teleconsulta
     *
     * Este endpoint permite a los doctores iniciar una sesión de teleconsulta
     * para citas virtuales. Genera un enlace de teleconsulta y actualiza
     * el estado de la cita a 'en_progreso'.
     *
     * @param Appointment $appointment Modelo de la cita virtual
     * @return JsonResponse Respuesta JSON con el enlace de teleconsulta
     *
     * @api {post} /api/doctor/appointments/{id}/start-teleconsultation Iniciar teleconsulta
     * @apiName StartTeleconsultation
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {Integer} id ID único de la cita virtual
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} data Enlace de teleconsulta
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * POST /api/doctor/appointments/123/start-teleconsultation
     */
    public function startTeleconsultation(Appointment $appointment)
    {
        // Verificar que la cita pertenece al doctor autenticado
        $doctor = Auth::user()->medicalStaff;
        if ($appointment->medical_staff_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to start this teleconsultation'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$appointment->isVirtual()) {
            return response()->json([
                'success' => false,
                'message' => 'This appointment is not virtual'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($appointment->status !== 'confirmada') {
            return response()->json([
                'success' => false,
                'message' => 'The appointment must be confirmed to start the teleconsultation'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar estado de la cita
        $appointment->update(['status' => 'en_progreso']);

        return response()->json([
            'success' => true,
            'data' => [
                'appointment' => $appointment->load(['patient.user']),
                'meeting_link' => $appointment->getTeleconsultationLink(),
                'meeting_password' => $appointment->meeting_password,
            ],
            'message' => 'Teleconsultation started successfully'
        ]);
    }

    /**
     * Finalizar sesión de teleconsulta
     *
     * Este endpoint permite a los doctores finalizar una sesión de teleconsulta
     * y actualizar el estado de la cita a 'completada'. También registra
     * la duración de la consulta y notas del doctor.
     *
     * @param Appointment $appointment Modelo de la cita virtual
     * @return JsonResponse Respuesta JSON confirmando la finalización
     *
     * @api {post} /api/doctor/appointments/{id}/end-teleconsultation Finalizar teleconsulta
     * @apiName EndTeleconsultation
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {Integer} id ID único de la cita virtual
     * @apiParam {String} [notes] Notas de la consulta
     * @apiParam {Integer} [duration] Duración en minutos
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos de la cita completada
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * POST /api/doctor/appointments/123/end-teleconsultation
     * {
     *   "notes": "Consulta completada exitosamente",
     *   "duration": 45
     * }
     */
    public function endTeleconsultation(Appointment $appointment)
    {
        // Verificar que la cita pertenece al doctor autenticado
        $doctor = Auth::user()->medicalStaff;
        if ($appointment->medical_staff_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to end this teleconsultation'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->status !== 'en_progreso') {
            return response()->json([
                'success' => false,
                'message' => 'The teleconsultation is not in progress'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar estado de la cita
        $appointment->update(['status' => 'completada']);

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['patient.user']),
            'message' => 'Teleconsultation ended successfully'
        ]);
    }

    /**
     * Ver disponibilidad personal
     */
    public function availability(Request $request)
    {
        $doctor = Auth::user()->medicalStaff;

        $query = DoctorAvailability::where('medical_staff_id', $doctor->id);

        if ($request->filled('date')) {
            $query->where(function ($q) use ($request) {
                $q->where('specific_date', $request->date)
                  ->orWhere(function ($subQ) use ($request) {
                      $subQ->whereNull('specific_date')
                           ->where('day_of_week', strtolower(now()->parse($request->date)->format('l')));
                  });
            });
        }

        $availability = $query->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data' => $availability,
            'message' => 'Availability obtained successfully'
        ]);
    }

    /**
     * Actualizar disponibilidad del doctor
     *
     * Este endpoint permite a los doctores actualizar sus horarios de disponibilidad.
     * Pueden configurar disponibilidad por día de la semana o fechas específicas,
     * incluyendo horarios de inicio y fin, y tipo de consulta.
     *
     * @param UpdateAvailabilityRequest $request Request validado con datos de disponibilidad
     * @return JsonResponse Respuesta JSON confirmando la actualización
     *
     * @api {put} /api/doctor/availability Actualizar disponibilidad
     * @apiName UpdateAvailability
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {String} [day_of_week] Día de la semana (monday, tuesday, etc.)
     * @apiParam {Date} [specific_date] Fecha específica (formato: Y-m-d)
     * @apiParam {String} start_time Hora de inicio (formato: H:i:s)
     * @apiParam {String} end_time Hora de fin (formato: H:i:s)
     * @apiParam {Boolean} is_available Si está disponible en este horario
     * @apiParam {String} [type] Tipo de consulta (presencial, virtual, ambos)
     * @apiParam {String} [notes] Notas adicionales
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos de disponibilidad actualizada
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * PUT /api/doctor/availability
     * {
     *   "day_of_week": "monday",
     *   "start_time": "09:00:00",
     *   "end_time": "17:00:00",
     *   "is_available": true,
     *   "type": "ambos"
     * }
     */
    public function updateAvailability(UpdateAvailabilityRequest $request)
    {
        try {
            $doctor = Auth::user()->medicalStaff;

            $availabilityData = $request->validated();
            $availabilityData['medical_staff_id'] = $doctor->id;

            $availability = DoctorAvailability::create($availabilityData);

            return response()->json([
                'success' => true,
                'data' => $availability,
                'message' => 'Availability updated successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating availability: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener lista de espera para la especialidad del doctor
     *
     * Este endpoint permite a los doctores consultar la lista de espera
     * de pacientes para su especialidad. Útil para identificar oportunidades
     * de asignar citas a pacientes en espera.
     *
     * @param Request $request Request con filtros opcionales
     * @return JsonResponse Respuesta JSON con la lista de espera
     *
     * @api {get} /api/doctor/waitlist Consultar lista de espera
     * @apiName GetWaitlist
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiParam {String} [status] Estado de la solicitud (waiting, assigned, cancelled)
     * @apiParam {String} [type] Tipo de cita (presencial, virtual)
     * @apiParam {Boolean} [urgent] Filtrar por solicitudes urgentes
     * @apiParam {Integer} [priority] Nivel de prioridad (1-5)
     * @apiParam {String} [sort_by] Campo para ordenar (priority, position, created_at)
     * @apiParam {String} [sort_dir] Dirección de ordenamiento (asc, desc)
     * @apiParam {Integer} [per_page] Número de elementos por página (default: 15)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos paginados de la lista de espera
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/doctor/waitlist?urgent=true&sort_by=priority&sort_dir=desc
     */
    public function waitlist(Request $request)
    {
        $doctor = Auth::user()->medicalStaff;

        $waitlist = \App\Models\AppointmentWaitlist::with(['patient.user', 'specialty'])
            ->where('preferred_doctor_id', $doctor->id)
            ->orWhere(function ($query) use ($doctor) {
                $query->whereNull('preferred_doctor_id')
                      ->where('specialty_id', $doctor->specialty_id);
            })
            ->waiting()
            ->orderedByPriority()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $waitlist,
            'message' => 'Waitlist obtained successfully'
        ]);
    }

    /**
     * Obtener citas del día actual
     *
     * Este endpoint permite a los doctores obtener todas sus citas
     * programadas para el día actual, ordenadas por hora de inicio.
     * Útil para la agenda diaria del doctor.
     *
     * @return JsonResponse Respuesta JSON con las citas del día
     *
     * @api {get} /api/doctor/appointments/today Obtener citas de hoy
     * @apiName GetTodayAppointments
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de citas del día actual
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/doctor/appointments/today
     */
    public function today()
    {
        $doctor = Auth::user()->medicalStaff;

        $appointments = Appointment::with(['patient.user', 'specialty'])
            ->where('medical_staff_id', $doctor->id)
            ->today()
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Appointments of the day obtained successfully'
        ]);
    }

    /**
     * Obtener citas de la semana actual
     *
     * Este endpoint permite a los doctores obtener todas sus citas
     * programadas para la semana actual, ordenadas por fecha y hora.
     * Útil para la planificación semanal del doctor.
     *
     * @return JsonResponse Respuesta JSON con las citas de la semana
     *
     * @api {get} /api/doctor/appointments/this-week Obtener citas de esta semana
     * @apiName GetThisWeekAppointments
     * @apiGroup DoctorAppointments
     * @apiPermission doctor
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de citas de la semana actual
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/doctor/appointments/this-week
     */
    public function thisWeek()
    {
        $doctor = Auth::user()->medicalStaff;

        $appointments = Appointment::with(['patient.user', 'specialty'])
            ->where('medical_staff_id', $doctor->id)
            ->thisWeek()
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Appointments of the week obtained successfully'
        ]);
    }


}
