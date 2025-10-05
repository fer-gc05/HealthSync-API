<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordFileRequest;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalRecordFileController extends Controller
{
    /**
     * Subir archivo adjunto a un registro médico
     * 
     * Permite a doctores y administradores subir archivos adjuntos a registros médicos.
     * Los doctores solo pueden subir archivos a sus propios registros.
     * Los administradores pueden subir archivos a cualquier registro.
     * 
     * @param StoreMedicalRecordFileRequest $request Datos del archivo a subir
     * @param MedicalRecord $medicalRecord Registro médico al cual adjuntar el archivo
     * @return JsonResponse Archivo subido exitosamente
     * 
     * Campos requeridos:
     * - file: Archivo a subir (PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT)
     * 
     * Campos opcionales:
     * - description: Descripción del archivo (máximo 500 caracteres)
     * 
     * Restricciones:
     * - Tamaño máximo: 10MB
     * - Tipos permitidos: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT
     * - Almacenamiento seguro en disco privado
     * 
     * Respuesta incluye información del archivo subido con ID generado.
     */
    public function store(StoreMedicalRecordFileRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        // Check permissions - only doctors and admins can upload files
        $user = auth()->user();
        if (!$user->hasRole(['doctor', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // For doctors, verify they can only upload to their own records
        if ($user->hasRole('doctor') && $medicalRecord->medical_staff_id !== $user->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->file('file');
        $path = $file->store('medical-records/' . $medicalRecord->id, 'private');
        
        $fileRecord = $medicalRecord->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $request->validated('description'),
            'uploaded_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $fileRecord
        ], Response::HTTP_CREATED);
    }

    /**
     * Listar archivos adjuntos de un registro médico
     * 
     * Permite a todos los usuarios autenticados ver la lista de archivos adjuntos
     * de un registro médico, con restricciones de acceso según el rol.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual listar archivos
     * @return JsonResponse Lista de archivos adjuntos
     * 
     * Restricciones de acceso:
     * - Pacientes: Solo archivos de sus propios registros médicos
     * - Doctores: Solo archivos de sus propios registros médicos
     * - Administradores: Archivos de cualquier registro médico
     * 
     * Respuesta incluye:
     * - Lista de archivos adjuntos
     * - Información del usuario que subió cada archivo
     * - Metadatos del archivo (nombre, tamaño, tipo, fecha de subida)
     */
    public function index(MedicalRecord $medicalRecord): JsonResponse
    {
        // Check permissions
        $user = auth()->user();
        
        if ($user->hasRole('patient')) {
            // Patients can only see files for their own records
            if ($medicalRecord->patient_id !== $user->patient->id) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }
        } elseif ($user->hasRole('doctor')) {
            // Doctors can only see files for their own records
            if ($medicalRecord->medical_staff_id !== $user->medicalStaff->id) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }
        }
        // Admins can see all files
        
        $files = $medicalRecord->files()->with('uploader')->get();
        
        return response()->json([
            'success' => true,
            'data' => $files
        ], Response::HTTP_OK);
    }

    /**
     * Descargar archivo adjunto de un registro médico
     * 
     * Permite a todos los usuarios autenticados descargar archivos adjuntos
     * de registros médicos, con restricciones de acceso según el rol.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual descargar archivo
     * @param int $fileId ID del archivo a descargar
     * @return StreamedResponse|JsonResponse Descarga del archivo o error
     * 
     * Restricciones de acceso:
     * - Pacientes: Solo archivos de sus propios registros médicos
     * - Doctores: Solo archivos de sus propios registros médicos
     * - Administradores: Archivos de cualquier registro médico
     * 
     * Respuesta:
     * - Archivo descargado con nombre original
     * - Headers apropiados para descarga segura
     * - Verificación de existencia del archivo en almacenamiento
     */
    public function download(MedicalRecord $medicalRecord, $fileId): StreamedResponse|JsonResponse
    {
        // Check permissions
        $user = auth()->user();
        
        if ($user->hasRole('patient')) {
            // Patients can only download files for their own records
            if ($medicalRecord->patient_id !== $user->patient->id) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }
        } elseif ($user->hasRole('doctor')) {
            // Doctors can only download files for their own records
            if ($medicalRecord->medical_staff_id !== $user->medicalStaff->id) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
            }
        }
        // Admins can download all files
        
        $file = $medicalRecord->files()->findOrFail($fileId);
        
        if (!Storage::disk('private')->exists($file->file_path)) {
            return response()->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }
        
        return Storage::disk('private')->download($file->file_path, $file->original_name);
    }

    /**
     * Eliminar archivo adjunto de un registro médico
     * 
     * Permite a doctores y administradores eliminar archivos adjuntos de registros médicos.
     * Los doctores solo pueden eliminar archivos de sus propios registros.
     * Los administradores pueden eliminar archivos de cualquier registro.
     * 
     * @param MedicalRecord $medicalRecord Registro médico del cual eliminar archivo
     * @param int $fileId ID del archivo a eliminar
     * @return JsonResponse Confirmación de eliminación
     * 
     * Restricciones de acceso:
     * - Solo doctores y administradores pueden eliminar archivos
     * - Doctores: Solo archivos de sus propios registros médicos
     * - Administradores: Archivos de cualquier registro médico
     * 
     * Acciones realizadas:
     * - Eliminación del archivo físico del almacenamiento
     * - Eliminación del registro de base de datos
     * - Verificación de permisos antes de la eliminación
     */
    public function destroy(MedicalRecord $medicalRecord, $fileId): JsonResponse
    {
        // Check permissions - only doctors and admins can delete files
        $user = auth()->user();
        if (!$user->hasRole(['doctor', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // For doctors, verify they can only delete files from their own records
        if ($user->hasRole('doctor') && $medicalRecord->medical_staff_id !== $user->medicalStaff->id) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $file = $medicalRecord->files()->findOrFail($fileId);
        
        // Delete the physical file
        if (Storage::disk('private')->exists($file->file_path)) {
            Storage::disk('private')->delete($file->file_path);
        }
        
        // Delete the database record
        $file->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ], Response::HTTP_OK);
    }
}
