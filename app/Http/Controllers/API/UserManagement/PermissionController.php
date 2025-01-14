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
        $permissions = Permission::with(['permissionRoleDetails.role'])->get();
    
        $permissionsData = $permissions->flatMap(function ($permission) {
            return $permission->permissionRoleDetails->map(function ($detail) use ($permission) {
                return [
                    'rolename' => $detail->role->name, // Role name
                    'name' => $permission->name,      // Permission name
                    'Permission' => [
                        'view' => in_array('read', $detail->permission_array), // Check 'read' access
                        'edit' => in_array('edit', $detail->permission_array), // Check 'edit' access
                    ],
                ];
            });
        });
    
        $groupedData = $permissionsData->groupBy('rolename')->map(function ($group, $rolename) {
            return [
                'rolename' => $rolename,
                'permissions' => $group->map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'Permission' => $item['Permission'],
                    ];
                }),
            ];
        })->values();
    
        return response()->json([
            'success' => true,
            'data' => $groupedData,
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|integer|exists:roles,id',
            'roles.*.actions' => 'required|array|min:1',
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
            'message' => 'Permission created successfully.',
            'data' => $permission,
        ], 201);
    }


    public function update(Request $request, $id): JsonResponse
    {
        // Find the permission by ID
        $permission = Permission::findOrFail($id);

        // Validate the request data
        $validatedData = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|integer|exists:roles,id',
            'roles.*.actions' => 'required|array|min:1',
        ]);

        // Update roles and actions for this permission
        foreach ($validatedData['roles'] as $roleData) {
            PermissionRoleDetail::updateOrCreate(
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
            'message' => 'Permission updated successfully.',
            'data' => $permission,
        ]);
    }





}