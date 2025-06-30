<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\CustomerController;

Route::group([ 'prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group([ 'middleware' => 'auth:api'], function() {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'userProfile']);
    });
});

Route::group(['middleware' => 'auth:api'], function() {
    Route::apiResource('roles', RoleController::class)->middleware('permission:manage-roles');
    Route::apiResource('employees', EmployeeController::class)->middleware('permission:manage-employees');

    // Add these new routes
    Route::apiResource('sources', SourceController::class)->middleware('permission:manage-sources');
    Route::apiResource('customers', CustomerController::class)->middleware('permission:manage-customers');

    // Customer attachments and notes routes
    Route::post('customers/{customer}/attachments', [CustomerController::class, 'addAttachment'])
        ->middleware('permission:manage-customers');
    Route::post('customers/{customer}/notes', [CustomerController::class, 'addNote'])
        ->middleware('permission:manage-customers');
});
