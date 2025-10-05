<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMedicalRecordRequest;
use App\Http\Requests\Admin\UpdateMedicalRecordRequest;
use App\Http\Requests\Admin\IndexMedicalRecordRequest;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAudit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MedicalRecordController extends Controller
{
    /**
     * Obtener lista completa de registros médicos (Administradores)
     * 
     * Permite a los administradores ver todos los registros médicos del sistema
     * con filtros administrativos avanzados. Acceso completo sin restricciones.
     * 
     * @param IndexMedicalRecordRequest $request Parámetros de filtrado opcionales
     * @return JsonResponse Lista paginada de todos los registros médicos
     * 
     * Filtros administrativos disponibles:
     * - patient_id: Filtrar por paciente específico
     * - medical_staff_id: Filtrar por doctor específico
     * - appointment_id: Filtrar por cita específica
     * - date_from: Fecha de inicio (YYYY-MM-DD)
     * - date_to: Fecha de fin (YYYY-MM-DD)
     * - has_prescriptions: Solo registros con prescripciones (true/false)
     * - has_files: Solo registros con archivos adjuntos (true/false)
     * - q: Búsqueda en contenido médico (subjetivo, objetivo, evaluación, plan, prescripciones)
     * - per_page: Número de registros por página (1-50, default: 15)
     * 
     * Respuesta incluye información completa de todos los registros médicos del sistema.
     */
    public function index(IndexMedicalRecordRequest $request): JsonResponse
    {
        $query = MedicalRecord::with(['patient', 'medicalStaff', 'appointment']);
        
        // Apply administrative filters
        if ($request->validated('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        
        if ($request->validated('medical_staff_id')) {
            $query->where('medical_staff_id', $request->medical_staff_id);
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
     * Crear un nuevo registro médico (Administradores)
     * 
     * Permite a los administradores crear registros médicos para cualquier
     * doctor y paciente del sistema. Acceso administrativo completo.
     * 
     * @param StoreMedicalRecordRequest $request Datos del registro médico
     * @return JsonResponse Registro médico creado con relaciones
     * 
     * Campos requeridos:
     * - appointment_id: ID de la cita médica
     * - patient_id: ID del paciente
     * - medical_staff_id: ID del doctor responsable
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
        $record = MedicalRecord::create($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $record->load(['patient', 'medicalStaff', 'appointment'])
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar cualquier registro médico (Administradores)
     * 
     * Permite a los administradores ver cualquier registro médico del sistema
     * sin restricciones. Acceso administrativo completo a todos los registros.
     * 
     * @param MedicalRecord $medicalRecord Registro médico a mostrar
     * @return JsonResponse Detalles completos del registro médico
     * 
     * Respuesta incluye:
     * - Datos completos del registro médico (SOAP notes, signos vitales, prescripciones)
     * - Información del paciente
     * - Información del doctor responsable
     * - Detalles de la cita médica
     * - Lista de archivos adjuntos
     * 
     * Nota: Los administradores pueden acceder a cualquier registro médico del sistema.
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $medicalRecord->load(['patient', 'medicalStaff', 'appointment', 'files'])
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar cualquier registro médico (Administradores)
     * 
     * Permite a los administradores modificar cualquier registro médico del sistema.
     * Acceso administrativo completo sin restricciones de doctor responsable.
     * 
     * @param UpdateMedicalRecordRequest $request Datos actualizados del registro
     * @param MedicalRecord $medicalRecord Registro médico a actualizar
     * @return JsonResponse Registro médico actualizado con relaciones
     * 
     * Campos actualizables:
     * - appointment_id: ID de la cita médica (opcional)
     * - patient_id: ID del paciente (opcional)
     * - medical_staff_id: ID del doctor responsable (opcional)
     * - subjective: Síntomas reportados por el paciente
     * - objective: Hallazgos del examen físico
     * - assessment: Evaluación y diagnóstico
     * - plan: Plan de tratamiento
     * - vital_signs: Signos vitales (objeto JSON)
     * - prescriptions: Prescripciones médicas
     * - recommendations: Recomendaciones para el paciente
     * 
     * Nota: Los administradores pueden modificar cualquier registro médico del sistema.
     */
    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->update($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $medicalRecord->load(['patient', 'medicalStaff', 'appointment'])
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar cualquier registro médico (Administradores)
     * 
     * Permite a los administradores eliminar cualquier registro médico del sistema.
     * Acceso administrativo completo sin restricciones de doctor responsable.
     * Se realiza un soft delete para mantener auditoría completa.
     * 
     * @param MedicalRecord $medicalRecord Registro médico a eliminar
     * @return JsonResponse Confirmación de eliminación
     * 
     * Nota: El registro no se elimina físicamente de la base de datos,
     * solo se marca como eliminado para mantener el historial completo.
     * Los administradores pueden eliminar cualquier registro médico del sistema.
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Obtener log completo de auditoría de cualquier registro médico (Administradores)
     * 
     * Proporciona acceso administrativo completo al log de auditoría de cualquier
     * registro médico del sistema. Sin restricciones de doctor responsable.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual obtener auditoría
     * @return JsonResponse Log completo de auditoría paginado
     * 
     * Información detallada incluida:
     * - Historial completo de todas las acciones realizadas
     * - Usuario responsable de cada acción
     * - Timestamps precisos de cada modificación
     * - Datos completos antes y después de cambios
     * - Información de sesión (IP address, User Agent)
     * - Paginación para grandes volúmenes de datos
     * 
     * Nota: Los administradores pueden ver la auditoría de cualquier registro médico del sistema.
     */
    public function audit($id): JsonResponse
    {
        // Find the medical record including soft deleted ones
        $medicalRecord = MedicalRecord::withTrashed()->findOrFail($id);
        
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
