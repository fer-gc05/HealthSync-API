<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserRoleController extends Controller
{
    /**
     * Asignar rol a un usuario específico
     *
     * Remueve todos los roles actuales del usuario y asigna el nuevo rol especificado.
     * La validación y autorización se manejan automáticamente a través del AssignRoleRequest.
     * Requiere permisos de administrador (manage-users).
     *
     * @OA\Put(
     *     path="/api/admin/users/{user}/role",
     *     summary="Assign role to user",
     *     tags={"Admin - User Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AssignRoleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully"
     *     )
     * )
     *
     * @param AssignRoleRequest $request Contiene el rol validado a asignar
     * @param User $user Usuario al que se le asignará el rol
     * @return JsonResponse Respuesta con el usuario actualizado y sus permisos
     */
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        try {
            // Remover todos los roles actuales y asignar el nuevo
            $user->syncRoles([$request->role]);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'permissions' => $user->getPermissionsViaRoles()->pluck('name')
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Obtener lista completa de usuarios con sus roles
     *
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get all users with roles",
     *     tags={"Admin - User Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully"
     *     )
     * )
     */
    public function users(): JsonResponse
    {
        try {
            $users = User::with('roles')->get();

            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'created_at' => $user->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'users' => $formattedUsers
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener usuarios filtrados por rol específico
     *
     * @OA\Get(
     *     path="/api/admin/users/role/{role}",
     *     summary="Get users by specific role",
     *     tags={"Admin - User Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", enum={"admin", "doctor", "patient"})
     *     )
     * )
     */
    public function usersByRole(string $role): JsonResponse
    {
        $validRoles = ['admin', 'doctor', 'patient'];

        if (!in_array($role, $validRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role specified'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $users = User::withRole($role)->get();

            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'created_at' => $user->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Users with role '{$role}' retrieved successfully",
                'users' => $formattedUsers,
                'count' => $formattedUsers->count()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users by role',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
