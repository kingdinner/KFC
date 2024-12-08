<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

trait SoftDeleteTrait
{
    /**
     * Soft delete a model instance.
     *
     * @param string $model
     * @param int $id
     * @return JsonResponse
     */
    public function performSoftDelete($model, $id): JsonResponse
    {
        $record = $model::find($id);

        if (!$record) {
            return response()->json(['message' => class_basename($model) . ' not found'], 404);
        }

        $record->delete();  // Soft delete the model

        return response()->json(['message' => class_basename($model) . ' and related records soft deleted'], 200);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @param string $model
     * @param int $id
     * @return JsonResponse
     */
    public function performRestore($model, $id): JsonResponse
    {
        $record = $model::withTrashed()->find($id);

        if (!$record) {
            return response()->json(['message' => class_basename($model) . ' not found'], 404);
        }

        $record->restore();  // Restore the model

        return response()->json(['message' => class_basename($model) . ' and related records restored'], 200);
    }
}
