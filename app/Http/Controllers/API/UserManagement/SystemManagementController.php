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
        $validatedData = $request->validate([
            'users' => 'required|array',
            'users.*.employee_id' => 'required|string|min:6|unique:authentication_accounts,employee_id',
            'users.*.email' => 'required|string|email|max:255|unique:authentication_accounts,email',
            'users.*.password' => 'required|string|min:6',
            'users.*.role' => 'required|string|exists:roles,name',
            'users.*.secret_question' => 'required|string|max:255',
            'users.*.secret_answer' => 'required|string|max:255',
            'users.*.employee.firstname' => 'required|string|max:255',
            'users.*.employee.lastname' => 'required|string|max:255',
            'users.*.employee.email_address' => 'required|string|email|unique:employees,email_address',
            'users.*.employee.dob' => 'nullable|date',
            'users.*.employee.nationality' => 'nullable|string|max:255',
            'users.*.employee.address' => 'nullable|string|max:255',
            'users.*.employee.city' => 'nullable|string|max:255',
            'users.*.employee.state' => 'nullable|string|max:255',
            'users.*.employee.zipcode' => 'nullable|string|max:255',
        ]);
        $timestamp = now();

        foreach ($validatedData['users'] as $data) {
            $accountData = [
                'employee_id' => $data['employee_id'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'secret_question' => $data['secret_question'],
                'secret_answer' => Hash::make($data['secret_answer']),
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            // Save AuthenticationAccount
            $account = AuthenticationAccount::create($accountData);

            // Assign roles
            $account->assignRole($data['role']);

            // Prepare employee data
            $employeeData = $data['employee'];
            $employeeData['authentication_account_id'] = $account->id;
            $employeeData['created_at'] = $timestamp;
            $employeeData['updated_at'] = $timestamp;

            // Save Employee
            Employee::create($employeeData);
        }

        return response()->json([
            'message' => 'Batch insert successful.',
            'inserted_count' => count($validatedData['users']),
        ]);
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
                    ]) ?: [], // Return an empty array if employee is null
                    'stores' => optional($account->employee)->stores
                        ? $account->employee->stores->map(function ($store) {
                            return $store->only(['id', 'name', 'store_code']);
                        })
                        : [], // Return an empty array if stores is null
                ];                
            }),
        ]);
    }
    
    public function findEmployeeById(Request $request, $id)
    {
        // Fetch the account with related data
        $account = AuthenticationAccount::with([
            'employee.stores:id,name,store_code', // Fetch stores directly
            'employee.leaves:id,employee_id,type,date_applied,date_ended,reporting_manager,reasons,status',
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
    
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|string|exists:roles,name',
            'secret_question' => 'nullable|string|max:255',
            'secret_answer' => 'nullable|string|max:255',
            'employee.firstname' => 'nullable|string|max:255',
            'employee.lastname' => 'nullable|string|max:255',
            'employee.email_address' => 'nullable|string|email|unique:employees,email_address,' . $id . ',authentication_account_id',
            'employee.dob' => 'nullable|date',
            'employee.nationality' => 'nullable|string|max:255',
            'employee.address' => 'nullable|string|max:255',
            'employee.city' => 'nullable|string|max:255',
            'employee.state' => 'nullable|string|max:255',
            'employee.zipcode' => 'nullable|string|max:255',
        ]);

        // Find the AuthenticationAccount by ID
        $account = AuthenticationAccount::findOrFail($id);

        // Update AuthenticationAccount details
        $account->update([
            'password' => isset($validatedData['password']) ? Hash::make($validatedData['password']) : $account->password,
            'secret_question' => $validatedData['secret_question'] ?? $account->secret_question,
            'secret_answer' => isset($validatedData['secret_answer']) ? Hash::make($validatedData['secret_answer']) : $account->secret_answer,
        ]);

        // Update Role if provided
        if (!empty($validatedData['role'])) {
            $account->syncRoles([$validatedData['role']]);
        }

        // Update Employee details if provided
        if (!empty($validatedData['employee'])) {
            $employeeData = $validatedData['employee'];
            if ($account->employee) {
                $account->employee->update($employeeData);
            } else {
                // If no employee exists, create one
                $employeeData['authentication_account_id'] = $account->id;
                Employee::create($employeeData);
            }
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => [
                'account_id' => $account->id,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'roles' => $account->roles->pluck('name'),
                'employee' => optional($account->employee)->only([
                    'id', 'firstname', 'lastname', 'email_address',
                    'address', 'city', 'state', 'zipcode'
                ]),
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

    private function fetchExternal()
    {
        return [
            [
                "employee_id" => "2025-0006",
                "email" => "user57@example.com",
                "password" => "password123",
                "role" => "admin",
                "secret_question" => "What is your pet's name?",
                "secret_answer" => "Charlie",
                "employee" => [
                    "firstname" => "John",
                    "lastname" => "Doe",
                    "email_address" => "john.doe@example.com",
                    "dob" => "1990-01-01",
                    "nationality" => "American",
                    "address" => "123 Main St",
                    "city" => "New York",
                    "state" => "NY",
                    "zipcode" => "10001",
                ],
            ],
            [
                "employee_id" => "2025-0004",
                "email" => "user85@example.com",
                "password" => "password456",
                "role" => "manager",
                "secret_question" => "What is your mother's maiden name?",
                "secret_answer" => "Smith",
                "employee" => [
                    "firstname" => "Jane",
                    "lastname" => "Doe",
                    "email_address" => "jane.doe@example.com",
                    "dob" => "1992-05-10",
                    "nationality" => "Canadian",
                    "address" => "456 Elm St",
                    "city" => "Toronto",
                    "state" => "ON",
                    "zipcode" => "M5H 2N2",
                ],
            ],
        ];        
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
