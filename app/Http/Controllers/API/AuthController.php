<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Models\AuthenticationAccount;
use Illuminate\Support\Facades\Log;
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
        Log::info('Login attempt with employee_id:', [$validatedData['employee_id']]);
        Log::info('User found:', [$user]);

        // Attempt to login using authService
        $loginResponse = $this->authService->attemptLogin($validatedData['employee_id'], $validatedData['password']);
        Log::info('Login response:', [$loginResponse]);
        // If login attempt fails
        if (!$loginResponse['success']) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // If login is successful, return the access token
        return response()->json([
            'token_type' => 'Bearer',
            'accessToken' => $loginResponse['accessToken']
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
