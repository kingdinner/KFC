<?php

namespace App\Http\Controllers\API\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Services\SoftDeleteTrait;
use App\Models\TMARAchievement;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use App\Traits\HandlesExternalAPI;
use App\Traits\HandlesHelperController;

class UserController extends Controller
{
    use SoftDeleteTrait;

    use HandlesExternalAPI, HandlesHelperController;    
    
    public function store(Request $request) {
        $timestamp = now();

        $employeeData = $this->fetchExternalEmployeeData();

        foreach ($employeeData['users'] as $data) {
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

            $account = AuthenticationAccount::create($accountData);
            $account->assignRole($data['role']);

            $employeeInfo = $data['employee'];
            $employeeInfo['authentication_account_id'] = $account->id;
            $employeeInfo['created_at'] = $timestamp;
            $employeeInfo['updated_at'] = $timestamp;

            Employee::create($employeeInfo);
        }

        return response()->json([
            'message' => 'Batch insert successful.',
            'inserted_count' => count($employeeData['users']),
        ]);
    }

    public function toggleUserLock(AuthenticationAccount $userid, Request $request)
    {
        if ($request->has('force_unlock') && $request->force_unlock === true) {
            if ($userid->is_active) {
                return response()->json([
                    'message' => 'User is already unlocked'
                ], 400);
            }

            $userid->update(['is_active' => true]);
            return response()->json(['message' => 'User has been forcefully unlocked'], 200);
        }

        $currentStatus = $userid->is_active ? 'unlocked' : 'locked';
        $newStatusValue = !$userid->is_active;
        $userid->update(['is_active' => $newStatusValue]);

        return response()->json([
            'message' => 'User lock status changed',
            'previous_status' => $currentStatus,
            'new_status' => $newStatusValue ? 'unlocked' : 'locked'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:authentication_accounts,employee_id',
            'secret_answer' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
    
        $user = AuthenticationAccount::where('employee_id', $request->employee_id)->first();
    
        if (!Hash::check($request->secret_answer, $user->secret_answer)) {
            return response()->json(['message' => 'The provided secret answer is incorrect.'], 403);
        }
    
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password has been successfully changed.']);
    }

    // public function forgotPassword(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:authentication_accounts,email',
    //     ]);
    
    //     $user = AuthenticationAccount::where('email', $request->email)->first();
    
    //     $newPassword = Str::random(8);
    //     $user->password = Hash::make($newPassword);
    //     $user->save();
    
    //     Mail::to($user->email)->send(new ResetPasswordMail($newPassword));
    
    //     return response()->json(['message' => 'A new password has been sent to your email address.']);
    // }
    
    public function show(Request $request)
    {
        $filterValue = trim($request->input('search', ''));
        $paginationSize = $request->input('perPage', 10);
    
        $query = AuthenticationAccount::with([
            'employee.stores:id,name,store_code',
            'roles:name'
        ]);
    
        if (!empty($filterValue)) {
            $lowerFilterValue = strtolower($filterValue);
    
            $query->whereRaw('LOWER(email) LIKE ?', ["%{$lowerFilterValue}%"])
                ->orWhereHas('employee', function ($q) use ($lowerFilterValue) {
                    $q->whereRaw('LOWER(firstname) LIKE ?', ["%{$lowerFilterValue}%"])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$lowerFilterValue}%"])
                    ->orWhereRaw('LOWER(email_address) LIKE ?', ["%{$lowerFilterValue}%"]);
                });
        }
    
        $userAccounts = $query->paginate($paginationSize);
    
        if ($userAccounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No accounts found for the given search term.',
            ], 404);
        }
    
        $userAccounts->getCollection()->transform(function ($user) {
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
                'stores' => $employee->stores->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name,
                        'store_code' => $store->store_code,
                    ];
                }),
            ] : null;
    
            $tmar = TMARAchievement::where('employee_id', $employee->id)->get();
    
            return [
                'user' => $user,
                'employee_details' => $employeeData,
                'tmar' => $tmar,
            ];
        });
    
        return $this->paginateResponse($userAccounts);
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

        $account = AuthenticationAccount::findOrFail($id);

        $account->update([
            'password' => isset($validatedData['password']) ? Hash::make($validatedData['password']) : $account->password,
            'secret_question' => $validatedData['secret_question'] ?? $account->secret_question,
            'secret_answer' => isset($validatedData['secret_answer']) ? Hash::make($validatedData['secret_answer']) : $account->secret_answer,
        ]);

        if (!empty($validatedData['role'])) {
            $account->syncRoles([$validatedData['role']]);
        }

        if (!empty($validatedData['employee'])) {
            $employeeData = $validatedData['employee'];
            if ($account->employee) {
                $account->employee->update($employeeData);
            } else {
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

    public function softDelete($id)
    {
        return $this->performSoftDelete(AuthenticationAccount::class, $id);
    }

    public function restore($id)
    {
        return $this->performRestore(AuthenticationAccount::class, $id);
    }
}
