<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialities\StoreSpecialtyRequest;
use App\Http\Requests\Specialities\UpdateSpecialtyRequest;
use App\Models\Specialty;
use App\Traits\HasSoftDeleteActions;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Specialities\IndexSpecialtyRequest;

/**
 * @tags Especialidades
 */
class SpecialtyController extends Controller
{
    use HasSoftDeleteActions;

    /**
     * Listar especialidades
     *
     * Obtiene un listado paginado de especialidades médicas del sistema con capacidades de búsqueda y filtrado.
     * - Usuarios públicos: Solo especialidades activas y no eliminadas
     * - Administradores: Pueden filtrar por estado (activas/inactivas) e incluir/filtrar eliminadas
     *
     *
     *  Parámetros disponibles:
     *  - q: Término de búsqueda (busca en name y description)
     *  - sort_by: Campo para ordenar (name, created_at, updated_at) - default: created_at
     *  - sort_dir: Dirección del ordenamiento (asc, desc) - default: desc
     *  - per_page: Cantidad de resultados por página (1-100) - default: 10
     *  - page: Número de página actual
     *
     *  Filtros exclusivos para admin:
     *  - active: Filtrar por especialidades activas (1) o inactivas (0)
     *  - with_trashed: Incluir especialidades eliminadas en los resultados (true=1 - false=0)
     *  - only_trashed: Mostrar solo especialidades eliminadas (true=1 - false=0)
     *
     * @param Request $request Parámetros de filtrado, búsqueda y paginación
     * @return JsonResponse Lista paginada de especialidades o error
     *
     * @response array{success: bool, data: Specialty[],current_page: int, last_page: int, per_page: int, total: int,next_page_url: string|null, prev_page_url: string|null,path: string, from: int|null, to: int|null,...}
     */
    public function index(IndexSpecialtyRequest $request): JsonResponse
    {
        try {
            $query = Specialty::query();

            // Búsqueda por término (disponible para todos)
            if ($request->filled('q')) {
                $searchTerm = $request->input('q');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Filtros exclusivos para admin
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                // Filtro por estado activo/inactivo
                if ($request->has('active')) {
                    $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
                }

                // Filtros de registros eliminados
                $onlyTrashed = filter_var($request->input('only_trashed'), FILTER_VALIDATE_BOOLEAN);
                $withTrashed = filter_var($request->input('with_trashed'), FILTER_VALIDATE_BOOLEAN);

                if ($onlyTrashed) {
                    $query->onlyTrashed();
                } elseif ($withTrashed) {
                    $query->withTrashed();
                }
            } else {
                // Usuarios públicos/pacientes/doctores: solo activas y no eliminadas
                $query->where('active', true);
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDir = $request->input('sort_dir', 'desc');

            $allowedSortFields = ['name', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

            $query->orderBy($sortBy, $sortDir);

            // Paginación
            $perPage = min(max((int)$request->input('per_page', 10), 1), 100);
            $specialties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                ...$specialties->toArray()
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
     * @response array{success: bool, message: string, data: Specialty}
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
     * @response array{success: bool, data: Specialty}
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
     * @response array{success: bool, message: string, data: Specialty}
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
