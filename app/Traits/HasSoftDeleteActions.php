<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HasSoftDeleteActions
{
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
