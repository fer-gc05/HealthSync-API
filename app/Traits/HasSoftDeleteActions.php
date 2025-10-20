<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HasSoftDeleteActions
{
    /**
     * Eliminar registro (Soft Delete)
     *
     * Elimina lógicamente un registro del sistema sin borrarlo permanentemente.
     * El registro puede ser restaurado posteriormente mediante el endpoint de restauración.
     *
     * @param Model $model Modelo a eliminar (inyección automática de Laravel)
     * @return JsonResponse Confirmación de eliminación exitosa o error
     *
     * @response array{success: bool, message: string}
     * @response 500 array{success: bool, message: string, errors: string}
     */
    public function destroy(Model $model): JsonResponse
    {
        try {
            $model->delete();

            return response()->json([
                'success' => true,
                'message' => $this->getEntityName() . ' deleted successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ' . strtolower($this->getEntityName()),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar registro eliminado
     *
     * Recupera un registro que fue eliminado mediante soft delete.
     * Solo pueden restaurarse registros eliminados lógicamente, no eliminaciones físicas.
     *
     * @param int $id ID del registro eliminado a restaurar
     * @return JsonResponse Registro restaurado con sus datos completos o error
     *
     * @response array{success: bool, message: string, data: object}
     * @response 500 array{success: bool, message: string, errors: string}
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $modelClass = $this->getModelClass();
            $model = $modelClass::withTrashed()->findOrFail($id);
            $model->restore();

            return response()->json([
                'success' => true,
                'message' => $this->getEntityName() . ' restored successfully.',
                'data' => $model
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore ' . strtolower($this->getEntityName()),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar registro permanentemente
     *
     * Elimina definitivamente un registro del sistema de forma irreversible.
     * Esta acción NO puede deshacerse. Use con extrema precaución.
     * Solo debe usarse para cumplir con políticas de retención de datos o GDPR.
     *
     * @param int $id ID del registro a eliminar permanentemente
     * @return JsonResponse Confirmación de eliminación permanente o error
     *
     * @response array{success: bool, message: string}
     * @response 500 array{success: bool, message: string, errors: string}
     */
    public function forceDestroy(int $id): JsonResponse
    {
        try {
            $modelClass = $this->getModelClass();
            $model = $modelClass::withTrashed()->findOrFail($id);
            $model->forceDelete();

            return response()->json([
                'success' => true,
                'message' => $this->getEntityName() . ' permanently deleted.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete ' . strtolower($this->getEntityName()),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    abstract protected function getModelClass(): string;
    abstract protected function getEntityName(): string;
}
