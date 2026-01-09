<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CriticalTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Google OAuth Routes
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.login');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    
    // 2FA Routes
    Route::get('/2fa/verify', [AuthController::class, 'show2FAForm'])->name('2fa.verify');
    Route::post('/2fa/verify', [AuthController::class, 'verify2FA'])->name('2fa.verify.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/settings/theme', [AuthController::class, 'updateTheme'])->name('settings.theme')->middleware('auth');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [TaskController::class, 'index'])->name('dashboard');
    Route::get('/organization/{organizationId}', [TaskController::class, 'organizationTasks'])->name('organization.tasks');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assignTask'])->name('tasks.assign');
    Route::post('/tasks/{task}/work/start', [TaskController::class, 'startWork'])->name('tasks.work.start');
    Route::post('/tasks/{task}/work/pause', [TaskController::class, 'pauseWork'])->name('tasks.work.pause');
    Route::get('/tasks/{task}/work/status', [TaskController::class, 'getWorkStatus'])->name('tasks.work.status');
    Route::get('/dashboard/stats', [TaskController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/organization/{organizationId}/stats', [TaskController::class, 'getOrganizationStats'])->name('organization.stats');
    
    // Employee Routes - Admin and Super Admin only (access controlled in EmployeeController)
    Route::resource('employees', EmployeeController::class);
    
    // Critical Tasks
    Route::get('/critical-tasks', [CriticalTaskController::class, 'index'])->name('critical-tasks.index');
    Route::get('/critical-tasks/count', [CriticalTaskController::class, 'count'])->name('critical-tasks.count');
    Route::post('/critical-tasks/{task}/toggle', [CriticalTaskController::class, 'toggle'])->name('critical-tasks.toggle');
    
    // Complete Task
    Route::post('/tasks/{task}/complete', [TaskController::class, 'completeTask'])->name('tasks.complete');
    // Incomplete Task (Admin and Super Admin only)
    Route::post('/tasks/{task}/incomplete', [TaskController::class, 'incompleteTask'])->name('tasks.incomplete');
    
    // Trash
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::get('/trash/count', [TrashController::class, 'count'])->name('trash.count');
    Route::post('/trash/{id}/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/{id}', [TrashController::class, 'forceDelete'])->name('trash.force-delete');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Search
    Route::get('/search/employee', [SearchController::class, 'searchEmployee'])->name('search.employee');
    
    // Role-based dashboards (placeholder routes)
    Route::get('/super-admin/dashboard', function () {
        return redirect()->route('dashboard')->with('info', 'Super Admin Dashboard');
    })->name('super-admin.dashboard');
    
    Route::get('/admin/dashboard', function () {
        return redirect()->route('dashboard')->with('info', 'Admin Dashboard');
    })->name('admin.dashboard');
});
