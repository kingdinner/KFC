<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LandingPage\HRFAQController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DataManagement\StoreController;
use App\Http\Controllers\API\DataManagement\PayRateController;
use App\Http\Controllers\API\DataManagement\StarStatusController;
use App\Http\Controllers\API\UserManagement\PermissionController;
use App\Http\Controllers\API\UserManagement\RoleController;
use App\Http\Controllers\API\UserManagement\UserController;
use App\Http\Controllers\API\UserManagement\LeaveController;
use App\Http\Controllers\API\UserManagement\BorrowTeamMemberController;
use App\Http\Controllers\API\UserManagement\AvailabilityController;
use App\Http\Controllers\API\ProxyController;
use App\Http\Controllers\API\TMAR\TmarReportController;
use App\Http\Controllers\API\TMAR\RatingController;

use App\Http\Controllers\API\ScheduleManagement\LaborManagementController;

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

    Route::post('/faq', [HRFAQController::class, 'storeOrUpdateFAQ']); // For creating or uploading FAQs
    Route::put('/faq/{id}', [HRFAQController::class, 'updateFAQ']); // For updating a specific FAQ by ID
    Route::post('/hr-rule', [HRFAQController::class, 'storeOrUpdateHRRule']); // For creating or uploading HR rules
    Route::put('/hr-rule/{id}', [HRFAQController::class, 'updateHRRule']); // For updating a specific HR Rule by ID
    Route::delete('/faq/{id}', [HRFAQController::class, 'softDeleteFAQ']); // For soft-deleting an FAQ
    Route::delete('/hr-rule/{id}', [HRFAQController::class, 'softDeleteHRRule']); // For soft-deleting an HR Rule    

    // User Management
    Route::apiResource('/users', UserController::class)->only(['store']);
    Route::put('/users/toggle-lock/{userid}', [UserController::class, 'toggleUserLock']);
    Route::post('/users/change-password', [UserController::class, 'resetPassword']);
    Route::post('/users/forgot-password', [UserController::class, 'forgotPassword']);
    Route::put('/users/update/{id}', [UserController::class, 'update']);
    Route::get('/users', [UserController::class, 'show']);
    Route::put('/users/{id}/role', [UserController::class, 'assignOrEditRoles']);

    // Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'getRoles']);
        Route::post('/', [RoleController::class, 'assignOrUpdate']);
        Route::delete('/', [RoleController::class, 'deleteRole']);
    });
    
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
    Route::middleware(['check.permission:Add/Edit User,delete'])->delete('/account/{id}/soft-delete', [UserController::class, 'softDelete']);
    Route::middleware(['check.permission:Delete User,delete'])->post('/account/{id}/restore', [UserController::class, 'restore']);

    // Permissions
    Route::apiResource('permissions', PermissionController::class)->except(['show', 'create', 'edit']);

    // Stores
    Route::put('/stores/{store_code}', [StoreController::class, 'update']);
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

    // Labor Management
    Route::post('/labor-schedule/generate', [LaborManagementController::class, 'generateLaborSchedule']);
    Route::post('/labor-schedule/generate', [LaborManagementController::class, 'generateLaborSchedule']);
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
