<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Http\Requests\Admin\IndexAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentAssignmentService;
use App\Services\AppointmentCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Administración de Citas
 *
 * Este controlador maneja todas las operaciones relacionadas con citas médicas
 * desde la perspectiva del administrador del sistema. Proporciona funcionalidades
 * para crear, leer, actualizar y eliminar citas, así como estadísticas y
 * gestión de disponibilidad de doctores.
 *
 * @package App\Http\Controllers\Admin
 * @author SaludOne Development Team
 * @version 1.0.0
 */
class AppointmentController extends Controller
{
    /**
     * Constructor del controlador
     *
     * Inyecta los servicios necesarios para la gestión de citas:
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
     * Listar todas las citas del sistema con filtros avanzados
     *
     * Este endpoint permite a los administradores obtener una lista paginada de todas
     * las citas del sistema con capacidades de filtrado y búsqueda avanzadas.
     *
     * @param IndexAppointmentRequest $request Request validado con filtros
     * @return JsonResponse Respuesta JSON con la lista de citas
     *
     * @api {get} /api/admin/appointments Obtener lista de citas
     * @apiName GetAppointments
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {String} [q] Término de búsqueda (nombre paciente, email, razón)
     * @apiParam {Integer} [doctor_id] ID del doctor para filtrar
     * @apiParam {Integer} [patient_id] ID del paciente para filtrar
     * @apiParam {Integer} [specialty_id] ID de la especialidad para filtrar
     * @apiParam {String} [type] Tipo de cita (presencial, virtual)
     * @apiParam {String} [status] Estado de la cita (pendiente, confirmada, completada, cancelada)
     * @apiParam {Boolean} [urgent] Filtrar por citas urgentes
     * @apiParam {Integer} [priority] Nivel de prioridad (1-5)
     * @apiParam {Date} [date_from] Fecha de inicio para filtrar
     * @apiParam {Date} [date_to] Fecha de fin para filtrar
     * @apiParam {String} [sort_by] Campo para ordenar (start_date, created_at, etc.)
     * @apiParam {String} [sort_dir] Dirección de ordenamiento (asc, desc)
     * @apiParam {Integer} [per_page] Número de elementos por página (default: 15)
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos paginados de las citas
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/admin/appointments?q=Juan&status=pendiente&sort_by=start_date&sort_dir=asc
     */
    public function index(IndexAppointmentRequest $request)
    {
        $query = Appointment::with(['patient.user', 'medicalStaff.user', 'specialty']);

        // Aplicar filtros
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('reason', 'like', '%' . $request->q . '%')
                    ->orWhereHas('patient.user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->q . '%')
                            ->orWhere('email', 'like', '%' . $request->q . '%');
                    })
                    ->orWhereHas('medicalStaff.user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->q . '%');
                    });
            });
        }

        if ($request->filled('doctor_id')) {
            $query->where('medical_staff_id', $request->doctor_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', $request->specialty_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('urgent')) {
            $query->where('urgent', $request->urgent);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
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
        ],  Response::HTTP_OK);
    }

    /**
     * Crear una nueva cita médica
     *
     * Este endpoint permite a los administradores crear nuevas citas médicas.
     * Si no se especifica un doctor, el sistema intentará asignar automáticamente
     * el mejor doctor disponible usando el algoritmo de asignación inteligente.
     * Si no hay doctores disponibles, la cita se agregará a la lista de espera.
     *
     * @param StoreAppointmentRequest $request Request validado con datos de la cita
     * @return JsonResponse Respuesta JSON con la cita creada o información de lista de espera
     *
     * @api {post} /api/admin/appointments Crear nueva cita
     * @apiName CreateAppointment
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} patient_id ID del paciente (requerido)
     * @apiParam {Integer} [medical_staff_id] ID del doctor (opcional, se asigna automáticamente si no se especifica)
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
     * POST /api/admin/appointments
     * {
     *   "patient_id": 1,
     *   "specialty_id": 2,
     *   "start_date": "2024-01-15 10:00:00",
     *   "end_date": "2024-01-15 11:00:00",
     *   "type": "presencial",
     *   "reason": "Consulta de rutina"
     * }
     */
    public function store(StoreAppointmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $appointmentData = $request->validated();

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
                        'message' => 'No doctors available. Request added to the waitlist.',
                        'waitlist' => true
                    ], 201);
                }
            }

            $appointment = Appointment::create($appointmentData);

            DB::commit();

            // Sincronizar con Google Calendar para TODAS las citas (después del commit)
            try {
                $this->calendarService->syncAppointment($appointment, 'create');
            } catch (\Exception $e) {
                \Log::error('Error syncing appointment with Google Calendar', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['patient.user', 'medicalStaff.user', 'specialty']),
                'message' => 'Appointment created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error creating appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar detalles de una cita específica
     *
     * Este endpoint permite a los administradores obtener todos los detalles
     * de una cita específica, incluyendo información del paciente, doctor,
     * especialidad y registros médicos relacionados.
     *
     * @param Appointment $appointment Modelo de la cita (inyectado automáticamente)
     * @return JsonResponse Respuesta JSON con los detalles de la cita
     *
     * @api {get} /api/admin/appointments/{id} Obtener detalles de cita
     * @apiName GetAppointment
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} id ID único de la cita
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos completos de la cita
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/admin/appointments/123
     */
    public function show(Appointment $appointment)
    {
        $appointment->load(['patient.user', 'medicalStaff.user', 'specialty', 'medicalRecords']);

        return response()->json([
            'success' => true,
            'data' => $appointment,
            'message' => 'Appointment obtained successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar una cita existente
     *
     * Este endpoint permite a los administradores actualizar los datos de una cita existente.
     * Si la cita está sincronizada con Google Calendar, se actualizará automáticamente
     * el evento en el calendario.
     *
     * @param UpdateAppointmentRequest $request Request validado con datos a actualizar
     * @param Appointment $appointment Modelo de la cita a actualizar
     * @return JsonResponse Respuesta JSON con la cita actualizada
     *
     * @api {put} /api/admin/appointments/{id} Actualizar cita
     * @apiName UpdateAppointment
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} id ID único de la cita
     * @apiParam {Integer} [medical_staff_id] ID del doctor
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
     * PUT /api/admin/appointments/123
     * {
     *   "status": "confirmada",
     *   "notes": "Paciente confirmó asistencia"
     * }
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        try {
            DB::beginTransaction();

            $appointment->update($request->validated());

            // Sincronizar cambios con Google Calendar
            if ($appointment->isSyncedWithGoogle()) {
                $this->calendarService->updateCalendarEvent($appointment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['patient.user', 'medicalStaff.user', 'specialty']),
                'message' => 'Appointment updated successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar una cita del sistema
     *
     * Este endpoint permite a los administradores eliminar permanentemente una cita.
     * Si la cita está sincronizada con Google Calendar, se eliminará automáticamente
     * el evento del calendario.
     *
     * @param Appointment $appointment Modelo de la cita a eliminar
     * @return JsonResponse Respuesta JSON confirmando la eliminación
     *
     * @api {delete} /api/admin/appointments/{id} Eliminar cita
     * @apiName DeleteAppointment
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} id ID único de la cita
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * DELETE /api/admin/appointments/123
     */
    public function destroy(Appointment $appointment)
    {
        try {
            DB::beginTransaction();

            // Eliminar del Google Calendar si está sincronizada
            if ($appointment->isSyncedWithGoogle()) {
                $this->calendarService->deleteCalendarEvent($appointment);
            }

            $appointment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ], Response::HTTP_OK    );

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error deleting appointment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sincronizar cita con Google Calendar
     *
     * Este endpoint permite sincronizar manualmente una cita con Google Calendar.
     * Útil para citas que no se sincronizaron automáticamente o para
     * re-sincronizar citas que tuvieron problemas de conectividad.
     *
     * @param Appointment $appointment Modelo de la cita a sincronizar
     * @return JsonResponse Respuesta JSON confirmando la sincronización
     *
     * @api {post} /api/admin/appointments/{id}/sync-google Sincronizar con Google Calendar
     * @apiName SyncAppointmentWithGoogle
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} id ID único de la cita
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * POST /api/admin/appointments/123/sync-google
     */
    public function syncWithGoogle(Appointment $appointment)
    {
        try {
            $this->calendarService->syncAppointment($appointment, 'create');

            return response()->json([
                'success' => true,
                'message' => 'Appointment synced with Google Calendar successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error syncing with Google Calendar: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener estadísticas del sistema de citas
     *
     * Este endpoint proporciona estadísticas completas sobre las citas del sistema,
     * incluyendo totales, distribuciones por tipo y estado, y métricas de rendimiento.
     * Útil para dashboards administrativos y reportes.
     *
     * @return JsonResponse Respuesta JSON con las estadísticas
     *
     * @api {get} /api/admin/appointments/stats Obtener estadísticas
     * @apiName GetAppointmentStats
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Estadísticas del sistema
     * @apiSuccess {Integer} data.total_appointments Total de citas
     * @apiSuccess {Integer} data.today_appointments Citas de hoy
     * @apiSuccess {Integer} data.this_week_appointments Citas de esta semana
     * @apiSuccess {Integer} data.virtual_appointments Citas virtuales
     * @apiSuccess {Integer} data.urgent_appointments Citas urgentes
     * @apiSuccess {Integer} data.synced_with_google Citas sincronizadas con Google
     * @apiSuccess {Integer} data.auto_assigned Citas asignadas automáticamente
     * @apiSuccess {Array} data.by_status Distribución por estado
     * @apiSuccess {Array} data.by_type Distribución por tipo
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/admin/appointments/stats
     */
    public function stats()
    {
        try {
            $stats = [
                'total_appointments' => Appointment::count(),
                'today_appointments' => Appointment::whereDate('start_date', today())->count(),
                'this_week_appointments' => Appointment::whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'virtual_appointments' => Appointment::where('type', 'virtual')->count(),
                'presencial_appointments' => Appointment::where('type', 'presencial')->count(),
                'urgent_appointments' => Appointment::where('urgent', true)->count(),
                'synced_with_google' => Appointment::where('calendar_synced', true)->count(),
                'auto_assigned' => Appointment::where('auto_assigned', true)->count(),
                'by_status' => Appointment::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
                'by_type' => Appointment::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->get(),
                'by_priority' => Appointment::select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics obtained successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obtaining statistics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener disponibilidad de doctores
     *
     * Este endpoint permite consultar la disponibilidad de doctores para una fecha
     * específica, opcionalmente filtrada por especialidad. Útil para la planificación
     * de citas y verificación de horarios disponibles.
     *
     * @param Request $request Request con parámetros de consulta
     * @return JsonResponse Respuesta JSON con la disponibilidad
     *
     * @api {get} /api/admin/appointments/availability Consultar disponibilidad
     * @apiName GetDoctorAvailability
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {String} [date] Fecha para consultar (formato: Y-m-d, default: hoy)
     * @apiParam {Integer} [specialty_id] ID de especialidad para filtrar
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Array} data Lista de doctores disponibles
     * @apiSuccess {String} message Mensaje de respuesta
     *
     * @apiExample Ejemplo de uso:
     * GET /api/admin/appointments/availability?date=2024-01-15&specialty_id=2
     */
    public function availability(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $specialtyId = $request->get('specialty_id');

        $availability = $this->assignmentService->getDoctorAvailability($date, $specialtyId);

        return response()->json([
            'success' => true,
            'data' => $availability,
            'message' => 'Availability obtained successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Asignar automáticamente el mejor doctor para una cita
     *
     * Este endpoint utiliza el algoritmo inteligente de asignación para encontrar
     * y asignar automáticamente el mejor doctor disponible para una cita específica.
     * Considera factores como carga de trabajo, experiencia y disponibilidad.
     *
     * @param Request $request Request con ID de la cita
     * @return JsonResponse Respuesta JSON con el doctor asignado
     *
     * @api {post} /api/admin/appointments/assign-optimal Asignar doctor automáticamente
     * @apiName AssignOptimalDoctor
     * @apiGroup AdminAppointments
     * @apiPermission admin
     *
     * @apiParam {Integer} appointment_id ID de la cita a asignar
     *
     * @apiSuccess {Boolean} success Estado de la operación
     * @apiSuccess {Object} data Datos de la cita con doctor asignado
     * @apiSuccess {String} message Mensaje de confirmación
     *
     * @apiExample Ejemplo de uso:
     * POST /api/admin/appointments/assign-optimal
     * {
     *   "appointment_id": 123
     * }
     */
    public function assignOptimal(Request $request)
    {
        $appointmentId = $request->get('appointment_id');
        $appointment = Appointment::findOrFail($appointmentId);

        $assignedDoctor = $this->assignmentService->assignOptimalDoctor(
            $appointment->specialty_id,
            $appointment->start_date,
            $appointment->end_date
        );

        if ($assignedDoctor) {
            $appointment->update([
                'medical_staff_id' => $assignedDoctor->id,
                'auto_assigned' => true
            ]);

            return response()->json([
                'success' => true,
                'data' => $appointment->load(['medicalStaff.user']),
                'message' => 'Doctor assigned automatically'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'message' => 'Could not assign a doctor automatically'
        ], Response::HTTP_BAD_REQUEST);
    }

}
