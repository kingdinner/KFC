<?php

namespace App\Http\Controllers\API\Usermanagement;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Employee;
use App\Services\SoftDeleteTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use App\Mail\AccountCreatedMail;

class SystemManagementController extends Controller
{
    use SoftDeleteTrait;
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|min:6|unique:authentication_accounts',
            'email' => 'required|string|email|max:255|unique:authentication_accounts',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name',
            'secret_question' => 'required|string|max:255',
            'secret_answer' => 'required|string|max:255',
        ]);
    
        $user = AuthenticationAccount::create([
            'employee_id' => $validated['employee_id'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'secret_question' => $validated['secret_question'],
            'secret_answer' => Hash::make($validated['secret_answer']),
        ]);
    
        $user->assignRole($validated['role']);
    
        return response()->json($user);
    }

    public function update(Request $request, AuthenticationAccount $userid)
    {
        // Determine the fields that were actually provided in the request
        $data = $request->only(['email', 'role']);

        $rules = [
            'role' => 'sometimes|string|exists:roles,name'
        ];
        
        if (isset($data['email']) && $data['email'] !== $userid->email) {
            $rules['email'] = 'email|max:255|unique:users,email,' . $userid->id;
        }

        $validated = $request->validate($rules);

        $changes = array_filter($validated, function($value, $key) use ($userid) {
            return $userid[$key] != $value;
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($changes)) {
            $userid->update($changes);
        }

        if (isset($validated['role']) && ($userid->roles->first()->name ?? null) !== $validated['role']) {
            $userid->syncRoles($validated['role']);
        }

        return response()->json(['message' => 'User updated successfully', 'changes' => $changes]);
    }

    public function toggleUserLock(AuthenticationAccount $userid, Request $request)
    {
        // If the request has 'force_unlock', we will unlock the user regardless of the current status
        if ($request->has('force_unlock') && $request->force_unlock === true) {
            if ($userid->is_active) {
                return response()->json([
                    'message' => 'User is already unlocked'
                ], 400);
            }

            // Force unlock the user
            $userid->update(['is_active' => true]);
            return response()->json(['message' => 'User has been forcefully unlocked'], 200);
        }

        // Get current status before toggling
        $currentStatus = $userid->is_active ? 'unlocked' : 'locked';

        // Toggle lock status (this will change the value)
        $newStatusValue = !$userid->is_active;
        $userid->update(['is_active' => $newStatusValue]);

        // Determine the new status after the update
        $newStatus = $newStatusValue ? 'unlocked' : 'locked';

        return response()->json([
            'message' => 'User lock status changed',
            'previous_status' => $currentStatus,
            'new_status' => $newStatus
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:authentication_accounts,email',
            'secret_answer' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
    
        $user = AuthenticationAccount::where('email', $request->email)->first();
    
        if (!Hash::check($request->secret_answer, $user->secret_answer)) {
            return response()->json(['message' => 'The provided secret answer is incorrect.'], 403);
        }
    
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password has been successfully changed.']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:authentication_accounts,email',
        ]);

        $user = AuthenticationAccount::where('email', $request->email)->first();

        // Generate a new random password
        $newPassword = Str::random(8);

        // Hash and update the user's password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Send an email with the new password
        Mail::to($user->email)->send(new ResetPasswordMail($newPassword));

        return response()->json(['message' => 'A new password has been sent to your email address.']);
    }

    public function assignOrUpdate(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'new_name' => 'nullable|string|max:255|unique:roles,name', // Optional for updating the role name
        ]);
    
        // Check if the role already exists by name
        $role = Role::where('name', $validated['name'])->where('guard_name', 'api')->first();
    
        if ($role) {
            // If the role exists, update it with the new name if provided
            $role->name = $validated['new_name'] ?? $role->name;
            $role->save();
    
            $message = 'Role updated successfully';
            $status = 200;
        } else {
            // If the role does not exist, create it
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'api',
            ]);
    
            $message = 'Role created successfully';
            $status = 201;
        }
    
        return response()->json([
            'success' => true,
            'message' => $message,
            'role' => $role,
        ], $status);
    }

    public function assignOrEditRoles(Request $request, AuthenticationAccount $userid)
    {
        $validated = $request->validate([
            'roles' => 'required|string|exists:roles,name' // Validate 'roles' as a single string and ensure it exists in the 'roles' table
        ]);
        
        // Retrieve the role to assign
        $role = Role::where('name', $validated['roles'])->first();
        if (!$role) {
            return response()->json(['message' => 'No valid role found'], 404);
        }
        
        // Sync the role with the user (this will replace any existing roles)
        $userid->syncRoles([$role]);
        
        return response()->json([
            'message' => 'Role has been assigned/updated successfully',
            'assigned_role' => $role->name  // Return the assigned role
        ]);
        
    }    

    public function updatePermission(Request $request)
    {
        // Ensure the authenticated user has permission to give permissions
        if (auth()->user()->cannot('grant-permissions')) {
            return response()->json(['message' => 'Forbidden: You do not have permission to grant permissions'], 403);
        }

        // Find the user and permission
        $user = AuthenticationAccount::find($request->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $permission = Permission::findByName($request->permission_name);
        if (!$permission) {
            return response()->json(['message' => 'Permission not found'], 404);
        }

        // Grant the permission
        $user->givePermissionTo($permission);

        return response()->json(['message' => 'Permission granted successfully']);
    }

    public function show(Request $request)
    {
        // Set items per page, defaulting to 10
        $perPage = $request->input('per_page', 10);
    
        // Paginate authentication accounts with relationships
        $userAccounts = AuthenticationAccount::with([
            'employee.stores:id,name,store_code',
            'roles:name'
        ])->paginate($perPage);
    
        // Format and return the response
        return response()->json([
            'success' => true,
            'data' => $userAccounts->map(function ($account) {
                return [
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'is_active' => $account->is_active,
                    'roles' => $account->roles->pluck('name'),
                    'employee' => optional($account->employee)->only([
                        'id', 'firstname', 'lastname', 'email_address', 
                        'address', 'city', 'state', 'zipcode'
                    ]),
                    'stores' => optional($account->employee)->stores->map(function ($store) {
                        return $store->only(['id', 'name', 'store_code']);
                    }),
                ];
            }),
        ]);
    }
    
    public function findEmployeeById(Request $request, $id)
    {
        // Fetch the account with related data
        $account = AuthenticationAccount::with([
            'employee.stores:id,name,store_code', // Fetch stores directly
            'employee.leaves:id,employee_id,date_applied,duration,reporting_manager,reasons,status',
            'roles:name',
            'employee.borrowedTeamMembers.borrowedStore:id,name,store_code',
            'employee.borrowedTeamMembers.transferredStore:id,name,store_code'
        ])->whereHas('employee', function ($query) use ($id) {
            $query->where('id', $id);
        })->first();
    
        // Handle employee not found
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }
    
        // Format and return employee details
        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'roles' => $account->roles->pluck('name'),
                'employee' => optional($account->employee)->only([
                    'id', 'firstname', 'lastname', 'email_address', 
                    'address', 'city', 'state', 'zipcode'
                ]),
                'stores' => optional($account->employee)->stores->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name,
                        'store_code' => $store->store_code,
                        'status' => optional($store->pivot)->status === null ? 'origin store' : $store->pivot->status,
                        'start_date' => optional($store->pivot)->start_date ?? null,
                        'end_date' => optional($store->pivot)->end_date ?? null,
                    ];
                }),
                'leaves' => optional($account->employee)->leaves->map(function ($leave) {
                    return $leave->only([
                        'id', 'date_applied', 'duration', 'reporting_manager', 'reasons', 'status'
                    ]);
                }),
                'borrowed_team_members' => optional($account->employee)->borrowedTeamMembers->map(function ($borrow) {
                    return [
                        'id' => $borrow->id,
                        'borrowed_store' => [
                            'id' => optional($borrow->borrowedStore)->id,
                            'name' => optional($borrow->borrowedStore)->name
                        ],
                        'transferred_store' => [
                            'id' => optional($borrow->transferredStore)->id,
                            'name' => optional($borrow->transferredStore)->name
                        ],
                        'borrowed_date' => $borrow->borrowed_date,
                        'borrowed_time' => $borrow->borrowed_time,
                        'transferred_date' => $borrow->transferred_date,
                        'transferred_time' => $borrow->transferred_time,
                        'borrow_type' => $borrow->borrow_type,
                        'skill_level' => $borrow->skill_level,
                        'status' => $borrow->status,
                        'reason' => $borrow->reason,
                    ];
                }),
            ],
        ]);
    }
    



    /**
     * Soft delete an account.
     */
    public function softDelete($id): JsonResponse
    {
        return $this->performSoftDelete(AuthenticationAccount::class, $id);
    }

    /**
     * Restore a soft-deleted account.
     */
    public function restore($id): JsonResponse
    {
        return $this->performRestore(AuthenticationAccount::class, $id);
    }

    public function forceResetPassword(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::find($request->employee_id);

        if (!$employee || !$employee->authenticationAccount) {
            return response()->json(['message' => 'Employee or associated account not found.'], 404);
        }

        $user = $employee->authenticationAccount;

        $newPassword = Str::random(12);

        $user->password = Hash::make($newPassword);
        $user->save();

        Mail::to($user->email)->send(new ResetPasswordMail($newPassword));

        return response()->json(['message' => 'Password has been reset and sent to the user\'s email.']);
    }

    public function getRoles()
    {
        // Fetch all roles from the database
        $roles = Role::all();

        return response()->json([
            'roles' => $roles
        ]);
    }

    public function deleteRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|exists:roles,name',
        ]);
    
        // Find the role by name
        $role = Role::where('name', $request->role_name)->firstOrFail();
    
        // Prevent deletion of admin and super admin roles
        if (in_array($role->name, ['super admin'])) {
            return response()->json([
                'message' => 'The admin and super admin roles cannot be deleted.'
            ], 403);
        }
    
        // Check if any users are assigned to this role
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Role cannot be deleted because it is assigned to one or more users.'
            ], 403);
        }
    
        // If no users are assigned, delete the role
        $role->delete();
    
        return response()->json([
            'message' => 'Role has been successfully deleted.'
        ]);
    }



    //change this to sync upon receive API
    // public function createAccountForEmployee(Request $request)
    // {
    //     $request->validate([
    //         'employee_id' => 'required|exists:employees,id',
    //         'email' => 'required|email|unique:authentication_accounts,email',
    //         'role' => 'required|exists:roles,name',
    //     ]);

    //     // Find the employee by ID
    //     $employee = Employee::findOrFail($request->employee_id);

    //     // Check if the employee already has an account
    //     if ($employee->authenticationAccount) {
    //         return response()->json([
    //             'message' => 'This employee already has an account.'
    //         ], 409);
    //     }

    //     // Generate a temporary password
    //     $temporaryPassword = Str::random(12);

    //     // Create a new account for the employee
    //     $account = AuthenticationAccount::create([
    //         'employee_id' => $employee->id,
    //         'email' => $request->email,
    //         'password' => Hash::make($temporaryPassword),
    //     ]);

    //     // Assign the role to the account
    //     $account->assignRole($request->role);

    //     // Send account creation email with temporary password
    //     Mail::to($account->email)->send(new AccountCreatedMail($account, $temporaryPassword));

    //     return response()->json([
    //         'message' => 'Account successfully created and email sent to employee.',
    //         'account' => $account
    //     ]);
    // }
}
