<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Return null for API requests, so no redirection happens
        if ($request->expectsJson()) {
            return null;
        }

        // For web-based apps, return the login route (this is not used for API requests)
        return throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'You are not authenticated.',
            ], 401)
        );
    }

    /**
     * Handle unauthenticated users in the API.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $guards
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function unauthenticated($request, array $guards)
    {
        // Return a custom response for unauthenticated API requests
        if ($request->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'You are not authenticated.',
                ], 401)
            );
        }

        // For web-based apps, handle default behavior (this won’t be hit in API requests)
        parent::unauthenticated($request, $guards);
    }
}
