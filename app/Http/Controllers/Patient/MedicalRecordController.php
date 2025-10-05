<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\IndexMedicalRecordRequest;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MedicalRecordController extends Controller
{
    /**
     * Obtener lista de registros médicos del paciente autenticado
     * 
     * Permite a los pacientes ver todos sus registros médicos con filtros básicos.
     * Solo muestra registros donde el paciente es el titular.
     * Los pacientes tienen acceso de solo lectura a sus registros médicos.
     * 
     * @param IndexMedicalRecordRequest $request Parámetros de filtrado opcionales
     * @return JsonResponse Lista paginada de registros médicos del paciente
     * 
     * Filtros disponibles:
     * - date_from: Fecha de inicio (YYYY-MM-DD)
     * - date_to: Fecha de fin (YYYY-MM-DD)
     * - per_page: Número de registros por página (1-50, default: 15)
     * 
     * Respuesta incluye:
     * - Datos del registro médico (SOAP notes, signos vitales, prescripciones)
     * - Información del doctor responsable
     * - Detalles de la cita médica
     * - Lista de archivos adjuntos disponibles
     */
    public function index(IndexMedicalRecordRequest $request): JsonResponse
    {
        $query = MedicalRecord::with(['patient', 'medicalStaff', 'appointment'])
            ->where('patient_id', auth()->user()->patient->id);
        
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
     * Mostrar un registro médico específico del paciente
     * 
     * Permite a los pacientes ver los detalles completos de uno de sus registros médicos.
     * Solo pueden acceder a registros donde son el paciente titular.
     * Incluye información completa del doctor, cita y archivos adjuntos.
     * 
     * @param MedicalRecord $medicalRecord Registro médico a mostrar
     * @return JsonResponse Detalles completos del registro médico
     * 
     * Respuesta incluye:
     * - Datos completos del registro médico (SOAP notes, signos vitales, prescripciones)
     * - Información del doctor responsable (nombre, especialidad)
     * - Detalles de la cita médica (fecha, motivo, tipo)
     * - Lista de archivos adjuntos disponibles para descarga
     * 
     * Nota: Los pacientes solo tienen acceso de lectura, no pueden modificar registros.
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        // Verify that the patient only sees their own records
        if ($medicalRecord->patient_id !== auth()->user()->patient->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        return response()->json([
            'success' => true,
            'data' => $medicalRecord->load(['patient', 'medicalStaff', 'appointment', 'files'])
        ], Response::HTTP_OK);
    }
}
