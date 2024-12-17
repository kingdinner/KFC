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


// Authentication Route
Route::post('login', [AuthController::class, 'login']);

Route::get('landing-page', [HRFAQController::class, 'landingPage']);

Route::middleware('auth:api')->group(function () {

    Route::post('tokenKeepAlive', [AuthController::class, 'tokenKeepAlive']);
    Route::post('logout', [AuthController::class, 'logout']);
    
    Route::post('/faq', [HRFAQController::class, 'storeOrUpdateFAQ']);
    Route::post('/hr-rule', [HRFAQController::class, 'storeOrUpdateHRRule']);

    // Add/Edit User - Create, Update, Delete actions
    Route::post('/users/register', [SystemManagementController::class, 'store']);
    Route::put('/users/update/{userid}', [SystemManagementController::class, 'update']);
    Route::put('/users/toggle-lock/{userid}', [SystemManagementController::class, 'toggleUserLock']);
    Route::post('/users/change-password', [SystemManagementController::class, 'resetPassword']);
    Route::post('/users/forgot-password', [SystemManagementController::class, 'forgotPassword']);
    
    Route::post('/create-account', [SystemManagementController::class, 'createAccountForEmployee']);
    
    Route::get('/roles', [SystemManagementController::class, 'getRoles']);
    Route::delete('/roles', [SystemManagementController::class, 'deleteRole']);
    Route::match(['post', 'put'], '/roles/{id?}', [SystemManagementController::class, 'assignOrUpdate']);
    // View User - Read action

    Route::get('/users', [SystemManagementController::class, 'show']);
    Route::get('/users/{id}', [SystemManagementController::class, 'findEmployeeById']);
    Route::put('/users/{userid}/assign-roles', [SystemManagementController::class, 'assignOrEditRoles']);
    
    // Approval
    Route::post('/leaves/{leave}/action', [LeaveController::class, 'handleLeaveAction']);
    Route::post('/borrow-team-members/{borrowTeamMember}/action', [BorrowTeamMemberController::class, 'handleBorrowRequestAction']);

    //leaves
    Route::post('/leaves/request', [LeaveController::class, 'createLeaveRequest']);
    Route::get('/leaves', [LeaveController::class, 'index']);

    //borrow and swap
    Route::controller(BorrowTeamMemberController::class)->group(function () {
        Route::get('/swap-team-members', 'swapIndex');
        Route::get('/borrow-team-members', 'borrowIndex');
    });
    // Route::get('/borrow-team-members', [BorrowTeamMemberController::class, 'index']);
    Route::post('/borrow-team-members/request', [BorrowTeamMemberController::class, 'createBorrowRequest']);

    // delete account
    Route::middleware(['check.permission:Add/Edit User,delete'])->delete('/account/{id}/soft-delete', [SystemManagementController::class, 'softDelete']);
    Route::middleware(['check.permission:Delete User,delete'])->post('/account/{id}/restore', [SystemManagementController::class, 'restore']);


    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::put('/permissions/{permission}', [PermissionController::class, 'update']);

    // Archive Users - Create, Update, Delete actions
    Route::post('/users/archive', [SystemManagementController::class, 'archive']);
    Route::put('/users/archive/update/{userid}', [SystemManagementController::class, 'updateArchive']);
    Route::delete('/users/archive/delete/{userid}', [SystemManagementController::class, 'destroyArchive']);

    //Store
    Route::get('stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::get('stores/{store}', [StoreController::class, 'show']);
    Route::put('stores/{store}', [StoreController::class, 'update']);
    Route::delete('stores/{store}', [StoreController::class, 'destroy']);
    Route::get('stores/search/{storeName}', [StoreController::class, 'search']);

    Route::prefix('pay-rates')->group(function () {
        Route::get('/', [PayRateController::class, 'index']);
        Route::post('/sync', [PayRateController::class, 'sync']);
    });
    
    // Star Status Routes
    Route::prefix('star-status')->group(function () {
        Route::get('/', [StarStatusController::class, 'index']);
        Route::post('/', [StarStatusController::class, 'store']);
        Route::put('/{id}', [StarStatusController::class, 'update']);
        Route::delete('/{id}', [StarStatusController::class, 'destroy']);
        Route::get('/search', [StarStatusController::class, 'search']);
    });
});


Route::fallback(function(){
    return response()->json([
        'status' => 'error',
        'message' => 'The requested route does not exist.'
    ], 404);
});

