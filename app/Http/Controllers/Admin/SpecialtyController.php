<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Specialities\StoreSpecialtyRequest;
use App\Http\Requests\Admin\Specialities\UpdateSpecialtyRequest;
use App\Models\Specialty;
use App\Traits\HasSoftDeleteActions;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Especialidades (Admin)
 */
class SpecialtyController extends Controller
{
    use HasSoftDeleteActions;

    /**
     * Listar especialidades
     *
     * Obtiene un listado resumido de todas las especialidades médicas registradas en el sistema.
     * Solo devuelve campos esenciales para mostrar en tablas o selectores.
     * Para obtener información completa use el endpoint de detalle individual.
     *
     * @param Request $request Parámetros de filtrado opcionales
     * @return JsonResponse Lista resumida de especialidades o error
     *
     * @response array{success: bool, data: array{id: int, name: string, active: bool}[]}
     *
     * Filtros disponibles:
     * - active: Filtrar por especialidades activas (true) o inactivas (false)
     * - with_trashed: Incluir especialidades eliminadas en los resultados (true/false)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Specialty::query();

            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            if ($request->boolean('with_trashed')) {
                $query->withTrashed();
            }

            $specialties = $query->latest()
                ->get(['id', 'name', 'active', 'deleted_at']);

            return response()->json([
                'success' => true,
                'data' => $specialties
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve specialties',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear especialidad
     *
     * Registra una nueva especialidad médica en el sistema.
     * El campo 'active' es opcional y por defecto es true.
     * El nombre debe ser único en el sistema.
     *
     * @param StoreSpecialtyRequest $request Datos de la especialidad a crear
     * @return JsonResponse Especialidad creada con sus datos completos o errores de validación
     *
     * @response array{success: bool, message: string, data: \App\Models\Specialty}
     *
     * Campos requeridos:
     * - name: Nombre de la especialidad (máximo 100 caracteres, único)
     *
     * Campos opcionales:
     * - description: Descripción detallada de la especialidad (máximo 255 caracteres)
     * - active: Estado de la especialidad (activa/inactiva, default: true)
     */
    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        try {
            $specialty = Specialty::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Specialty created successfully.',
                'data' => $specialty
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create specialty',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar especialidad
     *
     * Obtiene los detalles completos de una especialidad específica por su ID.
     * Incluye toda la información registrada: nombre, descripción completa, estado y fechas de auditoría.
     * Use este endpoint cuando necesite mostrar información detallada de una especialidad.
     *
     * @param Specialty $specialty Especialidad a mostrar (inyección automática por ID)
     * @return JsonResponse Detalles completos de la especialidad incluyendo timestamps
     *
     * @response array{success: bool, data: \App\Models\Specialty}
     */
    public function show(Specialty $specialty): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $specialty
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar especialidad
     *
     * Actualiza parcialmente los datos de una especialidad existente.
     * Solo se deben enviar los campos que se desean modificar.
     * El nombre debe seguir siendo único en el sistema.
     *
     * @param UpdateSpecialtyRequest $request Datos actualizados de la especialidad
     * @param Specialty $specialty Especialidad a actualizar (inyección automática por ID)
     * @return JsonResponse Especialidad actualizada con sus datos completos o errores de validación
     *
     * @response array{success: bool, message: string, data: \App\Models\Specialty}
     *
     * Campos actualizables:
     * - name: Nombre de la especialidad (máximo 100 caracteres, único)
     * - description: Descripción de la especialidad (máximo 255 caracteres)
     * - active: Estado de la especialidad (activa/inactiva)
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): JsonResponse
    {
        try {
            $specialty->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Specialty updated successfully.',
                'data' => $specialty
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update specialty',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la clase del modelo asociado al controlador
     *
     * @return string Namespace completo de la clase Specialty
     */
    protected function getModelClass(): string
    {
        return Specialty::class;
    }

    /**
     * Obtener el nombre de la entidad para mensajes personalizados
     *
     * @return string Nombre de la entidad "Specialty"
     */
    protected function getEntityName(): string
    {
        return 'Specialty';
    }
}
