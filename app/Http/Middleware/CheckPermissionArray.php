<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PermissionRoleDetail;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;

class CheckPermissionArray
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permissionName
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next, $permissionName, $action)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Log the permission name and action for debugging
        Log::info('Checking permission', ['permission_name' => $permissionName, 'action' => $action]);

        // Check if the user has a role
        $role = $user->roles->first();
        if (!$role) {
            Log::warning('User has no role', ['user_id' => $user->id]);
            return response()->json(['message' => 'Unauthorized - No Role'], 403);
        }

        // Explicitly resolve the Permission model
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            Log::warning('Permission not found', ['permission_name' => $permissionName]);
            return response()->json(['message' => 'Unauthorized - Permission Not Found'], 403);
        }

        // Check if the user's role has this permission with the required action (create, update, delete)
        $permissionDetail = PermissionRoleDetail::where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->first();

        if (!$permissionDetail || !in_array($action, $permissionDetail->permission_array)) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'action' => $action,
            ]);
            return response()->json(['message' => 'Unauthorized - Insufficient Permission'], 403);
        }

        // Log permission granted
        Log::info('Permission granted', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'action' => $action,
        ]);

        // If the permission exists and the user has the required action, proceed
        return $next($request);
    }
}
