<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Traits\HandlesHelperController;

use Illuminate\Pagination\LengthAwarePaginator;

class RoleController extends Controller
{
    use HandlesHelperController;
    public function assignOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'new_name' => 'nullable|string|max:255|unique:roles,name',
        ]);

        $role = Role::firstOrNew(['name' => $validated['name'], 'guard_name' => 'api']);

        if ($role->exists) {
            $role->name = $validated['new_name'] ?? $role->name;
            $role->save();

            $message = 'Role updated successfully';
            $status = 200;
        } else {
            $role->save();

            $message = 'Role created successfully';
            $status = 201;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'role' => $role,
        ], $status);
    }

    public function getRoles(Request $request)
    {
        $search = $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        $rolesQuery = Role::query();

        // Apply search filter
        if (!empty($search)) {
            $rolesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
                
                // Add numeric check for ID
                if (is_numeric($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }

        // Paginate the results
        $roles = $rolesQuery->paginate($perPage);

        $rolesData = $roles->getCollection()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'created_at' => $role->created_at ? $role->created_at->toDateTimeString() : null,
                'updated_at' => $role->updated_at ? $role->updated_at->toDateTimeString() : null,
            ];
        });

        // Create a new paginator for formatted roles data
        $formattedPaginator = new LengthAwarePaginator(
            $rolesData,
            $roles->total(),
            $roles->perPage(),
            $roles->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Return paginated response using the trait
        return $this->paginateResponse($formattedPaginator);
    }

    public function deleteRole(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);

        if (in_array($role->name, ['super-admin', 'admin'])) {
            return response()->json([
                'message' => 'The admin and super-admin roles cannot be deleted.'
            ], 403);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Role cannot be deleted because it is assigned to one or more users.'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role has been successfully deleted.'
        ], 200);
    }
}