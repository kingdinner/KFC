<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Models\AuthenticationAccount;
use App\Models\Permission;
use App\Models\TMARAchievement;
use App\Models\LaborSchedule;
use Carbon\Carbon;
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
        $validatedData = $request->validate([
            'employee_id' => 'required|string|exists:authentication_accounts,employee_id',
            'password' => 'required|string|min:6',
        ]);

        $user = AuthenticationAccount::with([
            'employee.stores:id,name,store_code',
            'roles:name'
        ])->where('employee_id', $validatedData['employee_id'])->first();

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is locked. Please contact support.'], 403);
        }

        $loginResponse = $this->authService->attemptLogin($validatedData['employee_id'], $validatedData['password']);
        if (!$loginResponse['success']) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $roles = $user->roles()->pluck('name');

        $roleIds = $user->roles()->pluck('id');
        $permissions = Permission::whereHas('permissionRoleDetails', function ($query) use ($roleIds) {
            $query->whereIn('role_id', $roleIds);
        })->with(['permissionRoleDetails' => function ($query) use ($roleIds) {
            $query->whereIn('role_id', $roleIds);
        }])->get();

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
                }, ['view' => false, 'edit' => false]),
            ];
        });

        $employee = $user->employee;
        $employeeData = $employee ? [
            'id' => $employee->id,
            'firstname' => $employee->firstname,
            'lastname' => $employee->lastname,
            'email_address' => $employee->email_address,
            'dob' => $employee->dob,
            'nationality' => $employee->nationality,
            'address' => $employee->address,
            'city' => $employee->city,
            'state' => $employee->state,
            'zipcode' => $employee->zipcode,
            'secret_question' => $user->secret_question,
            'stores' => $employee->stores->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'store_code' => $store->store_code,
                ];
            }),
        ] : null;

        $tmar = TMARAchievement::where('employee_id', $employee->id)->get();

        // Fetch labor schedule for the employee
        $schedules = [];
if ($employee) {
    // Extract the current month and year for filtering
    $currentMonth = Carbon::now()->format('Y_m'); // Example: "2025_01"

    // Retrieve the latest labor schedule for the current month
    $latestLaborSchedule = LaborSchedule::where('filename', 'like', "{$currentMonth}%")
        ->orderBy('created_at', 'desc') // Ensure to get the latest file
        ->first();

    if ($latestLaborSchedule) {
        // Access the schedule from the JSON stored in `schedule_array`
        $monthlySchedule = $latestLaborSchedule->schedule_array;

        // Filter and collect schedules for the employee
        $employeeSchedules = collect($monthlySchedule)->mapWithKeys(function ($schedule, $date) use ($employee, $latestLaborSchedule) {
            $filteredSchedules = collect($schedule)->filter(function ($item) use ($employee) {
                return $item['employee_id'] == $employee->id;
            })->map(function ($item) use ($latestLaborSchedule, $date) {
                // Add filename, schedule date, and metadata to each schedule entry
                return array_merge($item, [
                    'filename' => $latestLaborSchedule->filename,
                    'schedule_date' => $date,
                ]);
            });

            // Return the filtered schedules grouped by date
            return [$date => $filteredSchedules->values()->toArray()];
        })->filter(); // Remove empty dates

        // Append the schedules to the result array
        $schedules = $employeeSchedules->toArray();
    }
}
        
        return response()->json([
            'token_type' => 'Bearer',
            'accessToken' => $loginResponse['accessToken'],
            'roles' => $roles,
            'permissions' => $permissionsData,
            'employee_details' => $employeeData,
            'tmar' => $tmar,
            'schedule' => $schedules,
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
