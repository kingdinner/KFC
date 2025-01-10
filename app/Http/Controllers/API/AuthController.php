<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Models\AuthenticationAccount;
use App\Models\Permission;
class AuthController extends Controller
{
    protected $authService;
    protected $guard_name = 'api';
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        // Validate the input data
        $validatedData = $request->validate([
            'employee_id' => 'required|string|exists:authentication_accounts,employee_id',
            'password' => 'required|string|min:6',
        ]);
    
        // Retrieve the user by employee ID
        $user = AuthenticationAccount::where('employee_id', $validatedData['employee_id'])->first();
    
        // Check if the account is locked (i.e., is_active is false)
        if (!$user->is_active) {
            return response()->json(['message' => 'Account is locked. Please contact support.'], 403);
        }
    
        // Attempt to login using authService
        $loginResponse = $this->authService->attemptLogin($validatedData['employee_id'], $validatedData['password']);
        if (!$loginResponse['success']) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
    
        // Determine roles of the user
        $roles = $user->roles()->pluck('name');
    
        // Fetch permissions associated with the user's roles
        $roleIds = $user->roles()->pluck('id');
        $permissions = Permission::whereHas('permissionRoleDetails', function ($query) use ($roleIds) {
            $query->whereIn('role_id', $roleIds);
        })->with(['permissionRoleDetails' => function ($query) use ($roleIds) {
            $query->whereIn('role_id', $roleIds);
        }])->get();
    
        // Format permissions
        $permissionsData = $permissions->map(function ($permission) {
            $details = $permission->permissionRoleDetails->map(function ($detail) {
                return [
                    'view' => in_array('read', $detail->permission_array),
                    'edit' => in_array('edit', $detail->permission_array),
                ];
            });
    
            return [
                'name' => $permission->name,
                'Permission' => $details->reduce(function ($carry, $item) {
                    return [
                        'view' => $carry['view'] || $item['view'],
                        'edit' => $carry['edit'] || $item['edit'],
                    ];
                }, ['view' => false, 'edit' => false]), // Aggregate permissions across roles
            ];
        });
    
        // Return the response
        return response()->json([
            'token_type' => 'Bearer',
            'accessToken' => $loginResponse['accessToken'],
            'roles' => $roles, // Include roles
            'permissions' => $permissionsData, // Include permissions
        ]);
    }
    
    
    public function logout(Request $request)
    {
        $this->authService->revokeToken($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    public function tokenKeepAlive(Request $request)
    {
        $newExpiry = $this->authService->refreshTokenExpiry($request->user());

        return response()->json([
            'message' => 'Token expiry time refreshed',
            'expires_at' => $newExpiry->toDateTimeString(),
        ]);
    }
}
