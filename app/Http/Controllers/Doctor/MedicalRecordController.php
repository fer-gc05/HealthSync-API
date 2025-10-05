<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreMedicalRecordRequest;
use App\Http\Requests\Doctor\UpdateMedicalRecordRequest;
use App\Http\Requests\Doctor\IndexMedicalRecordRequest;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAudit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MedicalRecordController extends Controller
{
    /**
     * Obtener lista de registros médicos del doctor autenticado
     * 
     * Permite a los doctores ver todos sus registros médicos con filtros avanzados.
     * Solo muestra registros donde el doctor es el responsable médico asignado.
     * 
     * @param IndexMedicalRecordRequest $request Parámetros de filtrado opcionales
     * @return JsonResponse Lista paginada de registros médicos
     * 
     * Filtros disponibles:
     * - patient_id: Filtrar por paciente específico
     * - appointment_id: Filtrar por cita específica
     * - date_from: Fecha de inicio (YYYY-MM-DD)
     * - date_to: Fecha de fin (YYYY-MM-DD)
     * - has_prescriptions: Solo registros con prescripciones (true/false)
     * - has_files: Solo registros con archivos adjuntos (true/false)
     * - q: Búsqueda en contenido médico (subjetivo, objetivo, evaluación, plan, prescripciones)
     * - per_page: Número de registros por página (1-50, default: 15)
     */
    public function index(IndexMedicalRecordRequest $request): JsonResponse
    {
        $query = MedicalRecord::with(['patient', 'medicalStaff', 'appointment'])
            ->where('medical_staff_id', auth()->user()->medicalStaff->id);
        
        // Apply filters
        if ($request->validated('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        
        if ($request->validated('appointment_id')) {
            $query->where('appointment_id', $request->appointment_id);
        }
        
        if ($request->validated('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->validated('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        if ($request->validated('has_prescriptions')) {
            $query->whereNotNull('prescriptions');
        }
        
        if ($request->validated('has_files')) {
            $query->whereHas('files');
        }
        
        if ($request->validated('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('subjective', 'like', "%{$searchTerm}%")
                  ->orWhere('objective', 'like', "%{$searchTerm}%")
                  ->orWhere('assessment', 'like', "%{$searchTerm}%")
                  ->orWhere('plan', 'like', "%{$searchTerm}%")
                  ->orWhere('prescriptions', 'like', "%{$searchTerm}%")
                  ->orWhere('recommendations', 'like', "%{$searchTerm}%");
            });
        }
        
        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->validated('per_page') ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $records
        ], Response::HTTP_OK);
    }

    /**
     * Crear un nuevo registro médico
     * 
     * Permite a los doctores crear registros médicos para sus pacientes.
     * El doctor autenticado se asigna automáticamente como responsable médico.
     * Se crea automáticamente un registro de auditoría.
     * 
     * @param StoreMedicalRecordRequest $request Datos del registro médico
     * @return JsonResponse Registro médico creado con relaciones
     * 
     * Campos requeridos:
     * - appointment_id: ID de la cita médica
     * - patient_id: ID del paciente
     * 
     * Campos opcionales:
     * - subjective: Síntomas reportados por el paciente
     * - objective: Hallazgos del examen físico
     * - assessment: Evaluación y diagnóstico
     * - plan: Plan de tratamiento
     * - vital_signs: Signos vitales (objeto JSON)
     * - prescriptions: Prescripciones médicas
     * - recommendations: Recomendaciones para el paciente
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['medical_staff_id'] = auth()->user()->medicalStaff->id;
        
        $record = MedicalRecord::create($validatedData);
        
        return response()->json([
            'success' => true,
            'data' => $record->load(['patient', 'medicalStaff', 'appointment'])
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar un registro médico específico
     * 
     * Permite a los doctores ver los detalles completos de un registro médico.
     * Solo pueden acceder a registros donde son el doctor responsable asignado.
     * Incluye información del paciente, cita y archivos adjuntos.
     * 
     * @param MedicalRecord $medicalRecord Registro médico a mostrar
     * @return JsonResponse Detalles completos del registro médico
     * 
     * Respuesta incluye:
     * - Datos del registro médico (SOAP notes, signos vitales, prescripciones)
     * - Información del paciente
     * - Información del doctor responsable
     * - Detalles de la cita médica
     * - Lista de archivos adjuntos
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        // Verify the doctor can only see their own records
        if ($medicalRecord->medical_staff_id !== auth()->user()->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        return response()->json([
            'success' => true,
            'data' => $medicalRecord->load(['patient', 'medicalStaff', 'appointment', 'files'])
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar un registro médico existente
     * 
     * Permite a los doctores modificar registros médicos que han creado.
     * Solo el doctor responsable asignado puede actualizar el registro.
     * Se registra automáticamente en el historial de auditoría.
     * 
     * @param UpdateMedicalRecordRequest $request Datos actualizados del registro
     * @param MedicalRecord $medicalRecord Registro médico a actualizar
     * @return JsonResponse Registro médico actualizado con relaciones
     * 
     * Campos actualizables:
     * - appointment_id: ID de la cita médica (opcional)
     * - patient_id: ID del paciente (opcional)
     * - subjective: Síntomas reportados por el paciente
     * - objective: Hallazgos del examen físico
     * - assessment: Evaluación y diagnóstico
     * - plan: Plan de tratamiento
     * - vital_signs: Signos vitales (objeto JSON)
     * - prescriptions: Prescripciones médicas
     * - recommendations: Recomendaciones para el paciente
     */
    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        // Verify only the assigned doctor can update
        if ($medicalRecord->medical_staff_id !== auth()->user()->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $medicalRecord->update($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $medicalRecord->load(['patient', 'medicalStaff', 'appointment'])
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar un registro médico (soft delete)
     * 
     * Permite a los doctores eliminar registros médicos que han creado.
     * Solo el doctor responsable asignado puede eliminar el registro.
     * Se realiza un soft delete (eliminación lógica) para mantener auditoría.
     * Se registra automáticamente en el historial de auditoría.
     * 
     * @param MedicalRecord $medicalRecord Registro médico a eliminar
     * @return JsonResponse Confirmación de eliminación
     * 
     * Nota: El registro no se elimina físicamente de la base de datos,
     * solo se marca como eliminado para mantener el historial completo.
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        // Verify only the assigned doctor can delete
        if ($medicalRecord->medical_staff_id !== auth()->user()->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $medicalRecord->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Obtener registros médicos de un paciente específico
     * 
     * Permite a los doctores ver todos los registros médicos de un paciente específico
     * que han creado. Útil para revisar el historial médico completo de un paciente.
     * 
     * @param IndexMedicalRecordRequest $request Parámetros de filtrado opcionales
     * @param int $patientId ID del paciente
     * @return JsonResponse Lista paginada de registros médicos del paciente
     * 
     * Filtros disponibles:
     * - date_from: Fecha de inicio (YYYY-MM-DD)
     * - date_to: Fecha de fin (YYYY-MM-DD)
     * - per_page: Número de registros por página (1-50, default: 15)
     */
    public function patientRecords(IndexMedicalRecordRequest $request, $patientId): JsonResponse
    {
        $query = MedicalRecord::with(['patient', 'medicalStaff', 'appointment'])
            ->where('medical_staff_id', auth()->user()->medicalStaff->id)
            ->where('patient_id', $patientId);
        
        // Apply filters
        if ($request->validated('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->validated('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->validated('per_page') ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $records
        ], Response::HTTP_OK);
    }

    /**
     * Obtener historial de cambios de un registro médico
     * 
     * Muestra el historial completo de modificaciones realizadas en un registro médico.
     * Solo el doctor responsable puede ver el historial de sus registros.
     * Incluye información del usuario que realizó cada cambio.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual obtener historial
     * @return JsonResponse Lista de cambios realizados en el registro
     * 
     * Información incluida por cada cambio:
     * - Acción realizada (created, updated, deleted, restored)
     * - Usuario que realizó el cambio
     * - Fecha y hora del cambio
     * - Valores anteriores (para actualizaciones)
     * - Valores nuevos
     * - IP address y User Agent
     */
    public function history(MedicalRecord $medicalRecord): JsonResponse
    {
        // Verify the doctor can only see their own records
        if ($medicalRecord->medical_staff_id !== auth()->user()->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $history = MedicalRecordAudit::with('user')
            ->where('medical_record_id', $medicalRecord->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $history
        ], Response::HTTP_OK);
    }

    /**
     * Obtener log completo de auditoría de un registro médico
     * 
     * Proporciona acceso completo al log de auditoría de un registro médico específico.
     * Solo el doctor responsable puede ver la auditoría de sus registros.
     * Incluye todos los cambios realizados con detalles técnicos completos.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual obtener auditoría
     * @return JsonResponse Log completo de auditoría paginado
     * 
     * Información detallada incluida:
     * - Historial completo de todas las acciones
     * - Usuario responsable de cada acción
     * - Timestamps precisos
     * - Datos completos antes y después de cambios
     * - Información de sesión (IP, User Agent)
     * - Paginación para grandes volúmenes de datos
     */
    public function audit($id): JsonResponse
    {
        // Find the medical record including soft deleted ones
        $medicalRecord = MedicalRecord::withTrashed()->findOrFail($id);
        
        // Verify the doctor can only see their own records
        if ($medicalRecord->medical_staff_id !== auth()->user()->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $audit = MedicalRecordAudit::with('user')
            ->where('medical_record_id', $medicalRecord->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return response()->json([
            'success' => true,
            'data' => $audit
        ], Response::HTTP_OK);
    }
}
