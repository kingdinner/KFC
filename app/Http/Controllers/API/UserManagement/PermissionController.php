<?php

namespace App\Http\Controllers\API\Usermanagement;

use App\Http\Controllers\Controller;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use App\Models\PermissionRoleDetail;
use App\Models\Role;

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
                    $permissionArray = $detail->permission_array ?? [];
                    return [
                        'role_id' => $detail->role->id,
                        'rolename' => $detail->role->name,
                        'name' => $permission->name,
                        'Permission' => [
                            'view' => $permissionArray['view'] ?? false,
                            'edit' => $permissionArray['edit'] ?? false,
                        ],
                    ];
                });
        });

        $groupedData = $permissionsData->groupBy('rolename')->map(function ($group, $rolename) {
            return [
                'role_id' => $group->first()['role_id'],
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
        $validatedData = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*.rolename' => 'required|string',
            'roles.*.permissions' => 'required|array|min:1',
            'roles.*.permissions.*.name' => 'required|string',
            'roles.*.permissions.*.Permission.view' => 'required|boolean',
            'roles.*.permissions.*.Permission.edit' => 'required|boolean',
        ]);

        foreach ($validatedData['roles'] as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['rolename']],
                ['description' => $roleData['description'] ?? null]
            );

            foreach ($roleData['permissions'] as $permissionData) {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionData['name']]
                );

                PermissionRoleDetail::updateOrCreate(
                    [
                        'permission_id' => $permission->id,
                        'role_id' => $role->id,
                    ],
                    [
                        'permission_array' => [
                            'view' => $permissionData['Permission']['view'],
                            'edit' => $permissionData['Permission']['edit'],
                        ],
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Roles and permissions processed successfully.',
        ], 201);
    }


    public function update(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|integer|exists:roles,id',
            'roles.*.rolename' => 'required|string',
            'roles.*.permissions' => 'required|array|min:1',
            'roles.*.permissions.*.name' => 'required|string|exists:permissions,name',
            'roles.*.permissions.*.Permission.view' => 'required|boolean',
            'roles.*.permissions.*.Permission.edit' => 'required|boolean',
        ]);

        foreach ($validatedData['roles'] as $roleData) {
            $role = Role::findOrFail($roleData['role_id']);

            if ($role->name !== $roleData['rolename']) {
                $existingRole = Role::where('name', $roleData['rolename'])->first();

                if ($existingRole) {
                    return response()->json([
                        'success' => false,
                        'message' => "The role name '{$roleData['rolename']}' already exists.",
                    ], 400);
                }

                $role->update(['name' => $roleData['rolename']]);
            }

            foreach ($roleData['permissions'] as $permissionData) {
                $permission = Permission::where('name', $permissionData['name'])->firstOrFail();

                $rolePermission = PermissionRoleDetail::where([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ])->first();

                if ($rolePermission) {
                    $existingPermissions = $rolePermission->permission_array;
                    $updatedPermissions = [
                        'view' => $permissionData['Permission']['view'],
                        'edit' => $permissionData['Permission']['edit'],
                    ];

                    if ($existingPermissions !== $updatedPermissions) {
                        $rolePermission->update(['permission_array' => $updatedPermissions]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Roles and permissions updated successfully.',
        ]);
    }


    public function moduleList(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        $permissionsQuery = Permission::with(['permissionRoleDetails.role']);

        if (!empty($search)) {
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
                    ];
                });
        });

        $groupedData = $permissionsData->groupBy('rolename')->map(function ($group, $rolename) {
            return [
                'rolename' => $rolename,
                'modules' => $group->pluck('name')->toArray(),
            ];
        })->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $groupedData->count();
        $paginatedData = $groupedData->slice(($currentPage - 1) * $perPage, $perPage);
        $formattedPaginator = new LengthAwarePaginator(
            $paginatedData,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => $formattedPaginator->items(),
            'pagination' => [
                'current_page' => $formattedPaginator->currentPage(),
                'from' => $formattedPaginator->firstItem(),
                'to' => $formattedPaginator->lastItem(),
                'per_page' => $formattedPaginator->perPage(),
                'total' => $formattedPaginator->total(),
                'last_page' => $formattedPaginator->lastPage(),
                'next_page_url' => $formattedPaginator->nextPageUrl(),
                'prev_page_url' => $formattedPaginator->previousPageUrl(),
            ]
        ]);
    }

}