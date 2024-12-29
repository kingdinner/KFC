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
    Route::apiResource('/users', SystemManagementController::class)->only(['store', 'update', 'destroy']);
    Route::put('/users/toggle-lock/{userid}', [SystemManagementController::class, 'toggleUserLock']);
    Route::post('/users/change-password', [SystemManagementController::class, 'resetPassword']);
    Route::post('/users/forgot-password', [SystemManagementController::class, 'forgotPassword']);
    Route::get('/users/{id}', [SystemManagementController::class, 'findEmployeeById']);
    Route::put('/users/{userid}/assign-roles', [SystemManagementController::class, 'assignOrEditRoles']);

    // Roles
    Route::apiResource('/roles', SystemManagementController::class)->except(['create', 'edit']);

    // Approval
    Route::post('/leaves/{leave}/action', [LeaveController::class, 'handleLeaveAction']);
    Route::post('/borrow-team-members/{borrowTeamMember}/action', [BorrowTeamMemberController::class, 'handleBorrowRequestAction']);
    Route::post('/swap-team-members/{swapTeamMember}/action', [BorrowTeamMemberController::class, 'handleSwapRequestAction']);

    // Leaves
    Route::apiResource('/leaves', LeaveController::class)->only(['store', 'index']);

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
    Route::apiResource('stores', StoreController::class);
    Route::get('stores/search/{storeName}', [StoreController::class, 'search']);

    // Pay Rates
    Route::prefix('pay-rates')->group(function () {
        Route::get('/', [PayRateController::class, 'index']);
        Route::post('/sync', [PayRateController::class, 'sync']);
    });

    // Star Status
    Route::apiResource('star-status', StarStatusController::class);
});


Route::fallback(function(){
    return response()->json([
        'status' => 'error',
        'message' => 'The requested route does not exist.'
    ], 404);
});
