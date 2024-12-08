<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function attemptLogin($employee_id, $password)
    {
        if (!Auth::attempt(['employee_id' => $employee_id, 'password' => $password])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('appToken');
        $token = $tokenResult->token;
        $token->expires_at = now()->addHours(1);
        $token->save();

        return [
            'success' => true,
            'accessToken' => [
                'access_token' => $tokenResult->accessToken,
                'expires_at' => $token->expires_at->toDateTimeString(),
            ]
        ];
    }

    public function revokeToken($user)
    {
        $user->token()->revoke();
    }

    public function refreshTokenExpiry($user)
    {
        $token = $user->token();
        $token->expires_at = now()->addHours(1);
        $token->save();

        return $token->expires_at;
    }
}
