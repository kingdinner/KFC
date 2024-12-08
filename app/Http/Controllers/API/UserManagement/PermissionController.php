<?php

namespace App\Http\Controllers\API\Usermanagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use App\Models\PermissionRoleDetail;

class PermissionController extends Controller
{
    /**
     * Check if the authenticated user has a specific permission and action.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::with(['permissionRoleDetails' => function($query) {
            $query->with('role');
        }])->get();

        $permissionsData = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'roles' => $permission->permissionRoleDetails->map(function ($detail) {
                    return [
                        'role' => $detail->role->name,
                        'actions' => $detail->permission_array, 
                    ];
                }),
            ];
        });

        // Return the permissions with roles and actions in a JSON response
        return response()->json([
            'success' => true,
            'data' => $permissionsData,
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'roles' => 'required|array',
            'roles.*.role_id' => 'required|integer|exists:roles,id',
            'roles.*.actions' => 'required|array',
        ]);

        // Create the new permission
        $permission = Permission::create(['name' => $validatedData['name']]);

        // Attach roles and actions to the permission
        foreach ($validatedData['roles'] as $roleData) {
            PermissionRoleDetail::create([
                'permission_id' => $permission->id,
                'role_id' => $roleData['role_id'],
                'permission_array' => $roleData['actions'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission,
        ], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'roles' => 'required|array',
            'roles.*.role_id' => 'required|integer|exists:roles,id',
            'roles.*.actions' => 'required|array',
        ]);

        // Update the permission name
        $permission->update(['name' => $validatedData['name']]);

        // Update roles and actions for this permission
        foreach ($validatedData['roles'] as $roleData) {
            $permissionDetail = PermissionRoleDetail::updateOrCreate(
                [
                    'permission_id' => $permission->id,
                    'role_id' => $roleData['role_id'],
                ],
                [
                    'permission_array' => $roleData['actions'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission,
        ]);
    }


}