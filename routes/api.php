<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LandingPage\HRFAQController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DataManagement\StoreController;
use App\Http\Controllers\API\DataManagement\PayRateController;
use App\Http\Controllers\API\DataManagement\StarStatusController;
use App\Http\Controllers\API\UserManagement\PermissionController;
use App\Http\Controllers\API\UserManagement\SystemManagementController;
use App\Http\Controllers\API\UserManagement\LeaveController;
use App\Http\Controllers\API\UserManagement\BorrowTeamMemberController;
use App\Http\Controllers\API\UserManagement\AvailabilityController;
use App\Http\Controllers\API\ProxyController;
use App\Http\Controllers\API\TMAR\TmarReportController;
use App\Http\Controllers\API\TMAR\RatingController;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Experimental route
Route::match(['GET', 'POST', 'PUT', 'DELETE', 'HEAD'], '/proxy', [ProxyController::class, 'handle']);

// Authentication Route
Route::post('login', [AuthController::class, 'login']);

Route::get('landing-page', [HRFAQController::class, 'landingPage']);

Route::middleware('auth:api')->group(function () {

    Route::post('tokenKeepAlive', [AuthController::class, 'tokenKeepAlive']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('/faq', [HRFAQController::class, 'storeOrUpdateFAQ']);
    Route::post('/hr-rule', [HRFAQController::class, 'storeOrUpdateHRRule']);

    // User Management
    Route::apiResource('/users', SystemManagementController::class)->only(['store']);
    Route::put('/users/toggle-lock/{userid}', [SystemManagementController::class, 'toggleUserLock']);
    Route::post('/users/change-password', [SystemManagementController::class, 'resetPassword']);
    Route::post('/users/forgot-password', [SystemManagementController::class, 'forgotPassword']);
    Route::get('/users/{id}', [SystemManagementController::class, 'findEmployeeById']);
    Route::put('/users/update/{id}', [SystemManagementController::class, 'update']);
    Route::get('/users', [SystemManagementController::class, 'show']);
    Route::put('/users/{userid}/assign-roles', [SystemManagementController::class, 'assignOrEditRoles']);

    // Roles
    Route::apiResource('/roles', SystemManagementController::class)->except(['create', 'edit']);

    // Approval
    Route::post('/leaves/{leave}/action', [LeaveController::class, 'handleLeaveAction']);
    Route::post('/borrow-team-members/{borrowTeamMember}/action', [BorrowTeamMemberController::class, 'handleBorrowRequestAction']);
    Route::post('/swap-team-members/{swapTeamMember}/action', [BorrowTeamMemberController::class, 'handleSwapRequestAction']);

    // Leaves
    Route::apiResource('/leaves', LeaveController::class)->only(['store', 'index']);
    Route::post('/leaves/request', [LeaveController::class, 'createLeaveRequest']);

    // Availability
    Route::apiResource('availability', AvailabilityController::class);

    // Borrow and Swap
    Route::controller(BorrowTeamMemberController::class)->group(function () {
        Route::get('/swap-team-members', 'swapIndex');
        Route::get('/borrow-team-members', 'borrowIndex');
        Route::post('/borrow-team-members/request', 'createBorrowRequest');
    });

    // Delete Account
    Route::middleware(['check.permission:Add/Edit User,delete'])->delete('/account/{id}/soft-delete', [SystemManagementController::class, 'softDelete']);
    Route::middleware(['check.permission:Delete User,delete'])->post('/account/{id}/restore', [SystemManagementController::class, 'restore']);

    // Permissions
    Route::apiResource('permissions', PermissionController::class)->except(['show', 'create', 'edit']);

    // Archive Users
    Route::controller(SystemManagementController::class)->group(function () {
        Route::post('/users/archive', 'archive');
        Route::put('/users/archive/update/{userid}', 'updateArchive');
        Route::delete('/users/archive/delete/{userid}', 'destroyArchive');
    });

    // Stores
    Route::get('stores/search/{storeName?}', [StoreController::class, 'search']);
    Route::apiResource('stores', StoreController::class);

    // Pay Rates
    Route::prefix('pay-rates')->group(function () {
        Route::get('/', [PayRateController::class, 'index']);
        Route::post('/sync', [PayRateController::class, 'sync']);
    });

    // Star Status
    Route::prefix('star-status')->group(function () {
        Route::get('/', [StarStatusController::class, 'index']);
        Route::post('/', [StarStatusController::class, 'store']);
        Route::put('/{id}', [StarStatusController::class, 'update']);
        Route::delete('/{id}', [StarStatusController::class, 'destroy']);
        Route::get('/search/{status}', [StarStatusController::class, 'search']);
    });

    Route::prefix('tmar-summary')->group(function () {
        Route::get('/', [TmarReportController::class, 'index']);
    });

    Route::prefix('store-employees/{storeEmployeeId}/ratings')->group(function () {
        Route::get('/', [RatingController::class, 'index']);
        Route::get('{ratingId}', [RatingController::class, 'show']);
        Route::post('/', [RatingController::class, 'store']);
        Route::put('{ratingId}', [RatingController::class, 'update']);
    });
});

// remove this in production
Route::post('/reset-database', function () {
    try {
        // Reset the database and run seeders
        Artisan::call('migrate:fresh --seed');

        // Generate Passport keys manually
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (!File::exists($privateKeyPath) || !File::exists($publicKeyPath)) {
            shell_exec('openssl genrsa -out ' . $privateKeyPath . ' 4096');
            shell_exec('openssl rsa -in ' . $privateKeyPath . ' -pubout -out ' . $publicKeyPath);
        }

        // Insert or update OAuth clients
        $personalAccessClientId = DB::table('oauth_clients')->updateOrInsert(
            ['personal_access_client' => true],
            [
                'name' => 'Personal Access Client',
                'secret' => bin2hex(random_bytes(32)),
                'redirect' => 'http://localhost',
                'password_client' => false,
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $passwordGrantClientId = DB::table('oauth_clients')->updateOrInsert(
            ['password_client' => true],
            [
                'name' => 'Password Grant Client',
                'secret' => bin2hex(random_bytes(32)),
                'redirect' => 'http://localhost',
                'personal_access_client' => false,
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Link the personal access client
        DB::table('oauth_personal_access_clients')->updateOrInsert(
            ['client_id' => $personalAccessClientId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return response()->json([
            'message' => 'Database reset, seeded successfully, and Passport keys and clients generated/updated.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
});

Route::fallback(function(){
    return response()->json([
        'status' => 'error',
        'message' => 'The requested route does not exist.'
    ], 404);
});
