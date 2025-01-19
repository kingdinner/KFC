<?php

namespace App\Http\Controllers\API\Usermanagement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use App\Models\PermissionRoleDetail;

use Illuminate\Pagination\LengthAwarePaginator;
class PermissionController extends Controller
{

    use HandlesHelperController;    
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
    
        $permissionsQuery = Permission::with(['permissionRoleDetails.role']);
    
        if ($search) {
            $permissionsQuery->whereHas('permissionRoleDetails.role', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }
    
        $permissions = $permissionsQuery->get();
    
        $permissionsData = $permissions->flatMap(function ($permission) use ($search) {
            return $permission->permissionRoleDetails
                ->filter(function ($detail) use ($search) {
                    return !$search || stripos($detail->role->name, $search) !== false; 
                })
                ->map(function ($detail) use ($permission) {
                    return [
                        'rolename' => $detail->role->name, 
                        'name' => $permission->name,
                        'Permission' => [
                            'view' => in_array('read', $detail->permission_array), 
                            'edit' => in_array('edit', $detail->permission_array),
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
    
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = collect($groupedData)->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($pagedData, $groupedData->count(), $perPage, $currentPage);
    
        return $this->paginateResponse($paginator);
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