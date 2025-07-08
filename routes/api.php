<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EmployeeNotificationController;
use App\Http\Controllers\LogsController;

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

    // dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:view-dashboard');
    Route::get('dashboardCharts', [DashboardController::class, 'charts'])->middleware('permission:view-dashboard');
    Route::get('dashboardStatistice', [DashboardController::class, 'statistice'])->middleware('permission:view-dashboard');

    // roles
    Route::apiResource('roles', RoleController::class)->middleware('permission:manage-roles');

    // employee
    Route::apiResource('employees', EmployeeController::class)->middleware('permission:manage-employees');
    Route::delete('employees/{employee}/attachments/{attachment}', [EmployeeController::class, 'deleteAttachment'])
        ->middleware('permission:manage-employees');
    Route::post('/employees/transfer-customers', [EmployeeController::class, 'transferCustomers'])
        ->middleware('permission:manage-employees');

    // source
    Route::apiResource('sources', SourceController::class)->middleware('permission:manage-sources');
    Route::get('/source-types', [SourceController::class, 'getSourceTypes'])
        ->middleware('permission:manage-sources');
    Route::get('/sources/by-manager/{managerId}', [SourceController::class, 'getSourcesByManager'])
        ->middleware('permission:manage-sources');

    // customers
    Route::apiResource('customers', CustomerController::class)->middleware('permission:manage-customers');
    Route::post('customers/{customer}/attachments', [CustomerController::class, 'addAttachment'])
        ->middleware('permission:manage-customers');
    Route::post('customers/{customer}/notes', [CustomerController::class, 'addNote'])
        ->middleware('permission:manage-customers');

    // Branches
    Route::apiResource('branches', BranchController::class)->middleware('permission:manage-branches');

    // Rooms (nested under branches)
    Route::prefix('branches/{branchId}')->group(function() {
        Route::apiResource('rooms', RoomController::class)->middleware('permission:manage-rooms')->except(['update']);

        // Offices (nested under rooms)
        Route::prefix('rooms/{roomId}')->group(function() {
            Route::apiResource('offices', OfficeController::class)->middleware('permission:manage-offices');
            // Desks
            Route::prefix('/offices/{officeId}')->group(function() {
                Route::post('/desks', [DeskController::class, 'addDeskToSharedOffice'])
                    ->middleware('permission:manage-offices');
                Route::get('/desks', [DeskController::class, 'listDesks'])
                    ->middleware('permission:manage-offices');
            });

        });
    });

    // Contracts
    Route::apiResource('contracts', ContractController::class)->middleware('permission:manage-contracts');
    Route::post('contracts/{contract}/attachments', [ContractController::class, 'addAttachment'])
        ->middleware('permission:manage-contracts');
    Route::delete('contracts/{contract}/attachments/{attachment}', [ContractController::class, 'deleteAttachment'])
        ->middleware('permission:manage-contracts');
    Route::post('/contracts/{id}/renew', [ContractController::class, 'renew'])->middleware('permission:manage-contracts');
    Route::patch('/contracts/{id}/status', [ContractController::class, 'updateStatus'])->middleware('permission:manage-contracts');

    // Inquiries
    Route::apiResource('inquiries', InquiryController::class)->middleware('permission:manage-inquiries');

    // Reports
    Route::get('orderClients', [ReportController::class, 'orderClients'])->middleware('permission:view-dashboard')
        ->middleware('permission:manage-reports');
    Route::get('finance', [ReportController::class, 'finance'])->middleware('permission:view-dashboard')
        ->middleware('permission:manage-reports');
    Route::get('contract', [ReportController::class, 'contract'])->middleware('permission:view-dashboard')
        ->middleware('permission:manage-reports');

    // notifications
   Route::prefix('employee/notifications')->middleware('permission:manage-notifications')->group(function () {
        Route::get('/', [EmployeeNotificationController::class, 'index']);
        Route::get('/unread', [EmployeeNotificationController::class, 'unread']);
        Route::get('/read', [EmployeeNotificationController::class, 'read']);
        Route::post('/mark-all-as-read', [EmployeeNotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/mark-as-read', [EmployeeNotificationController::class, 'markAsRead']);
    });

    // logs
    Route::get('/logs', [LogsController::class, 'index'])->middleware('permission:manage-logs');

});
