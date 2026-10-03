<?php

use Illuminate\Support\Facades\Route;

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

Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'form'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
});
Route::middleware(['auth', 'active', 'auth.session'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [\App\Http\Controllers\AuthController::class, 'profile'])->name('profile');
    Route::put('/profile/password', [\App\Http\Controllers\AuthController::class, 'password'])->name('profile.password');
    Route::get('/employee-photos/{employee}', [\App\Http\Controllers\EmployeeController::class, 'photo'])->name('employees.photo');
    foreach (['rental' => 'employees', 'own' => 'own-employees'] as $workforce => $prefix) {
        $controller = \App\Http\Controllers\EmployeeController::class;
        Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
        Route::get('/'.$prefix.'/create', [$controller, 'create'])->defaults('workforce', $workforce)->name($prefix.'.create');
        Route::get('/'.$prefix.'/{employee}/cv', [$controller, 'cv'])->defaults('workforce', $workforce)->name($prefix.'.cv');
        Route::post('/'.$prefix, [$controller, 'store'])->defaults('workforce', $workforce)->name($prefix.'.store');
        Route::get('/'.$prefix.'/{employee}', [$controller, 'show'])->defaults('workforce', $workforce)->name($prefix.'.show');
        Route::get('/'.$prefix.'/{employee}/edit', [$controller, 'edit'])->defaults('workforce', $workforce)->name($prefix.'.edit');
        Route::put('/'.$prefix.'/{employee}', [$controller, 'update'])->defaults('workforce', $workforce)->name($prefix.'.update');
        Route::delete('/'.$prefix.'/{employee}', [$controller, 'destroy'])->defaults('workforce', $workforce)->name($prefix.'.destroy');
    }
    foreach (['companies', 'designations'] as $lookup) {
        $controller = \App\Http\Controllers\LookupController::class;
        Route::get('/'.$lookup, [$controller, 'index'])->defaults('lookup', $lookup)->name($lookup.'.index');
        Route::post('/'.$lookup, [$controller, 'store'])->defaults('lookup', $lookup)->name($lookup.'.store');
        Route::put('/'.$lookup.'/{id}', [$controller, 'update'])->defaults('lookup', $lookup)->name($lookup.'.update');
        Route::delete('/'.$lookup.'/{id}', [$controller, 'destroy'])->defaults('lookup', $lookup)->name($lookup.'.destroy');
    }
    foreach (['rental' => 'timesheets', 'own' => 'attendance'] as $workforce => $prefix) {
        $controller = \App\Http\Controllers\TimesheetController::class;
        Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
        Route::get('/'.$prefix.'/create', [$controller, 'create'])->defaults('workforce', $workforce)->name($prefix.'.create');
        Route::post('/'.$prefix, [$controller, 'store'])->defaults('workforce', $workforce)->name($prefix.'.store');
        Route::get('/'.$prefix.'/{timesheet}/edit', [$controller, 'edit'])->defaults('workforce', $workforce)->name($prefix.'.edit');
        Route::put('/'.$prefix.'/{timesheet}', [$controller, 'update'])->defaults('workforce', $workforce)->name($prefix.'.update');
        Route::delete('/'.$prefix.'/{timesheet}', [$controller, 'destroy'])->defaults('workforce', $workforce)->name($prefix.'.destroy');
        Route::post('/'.$prefix.'/{timesheet}/review', [$controller, 'review'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.review');
    }
    foreach (['rental' => 'payrolls', 'own' => 'salaries'] as $workforce => $prefix) {
        $controller = \App\Http\Controllers\PayrollController::class;
        Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
        Route::get('/'.$prefix.'/export', [$controller, 'export'])->defaults('workforce', $workforce)->name($prefix.'.export');
        Route::get('/'.$prefix.'/{payroll}/pdf', [$controller, 'pdf'])->defaults('workforce', $workforce)->name($prefix.'.pdf');
        Route::get('/'.$prefix.'/{payroll}', [$controller, 'show'])->defaults('workforce', $workforce)->name($prefix.'.show');
        Route::post('/'.$prefix, [$controller, 'store'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.store');
        Route::put('/'.$prefix.'/{payroll}', [$controller, 'update'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.update');
        Route::post('/'.$prefix.'/{payroll}/paid', [$controller, 'paid'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.paid');
        Route::delete('/'.$prefix.'/{payroll}', [$controller, 'destroy'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
    }
    Route::middleware('super_admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show', 'destroy']);
        Route::get('/activity', function () { return view('activity', ['logs' => \App\Models\ActivityLog::with('user')->latest()->paginate(30)]); })->name('activity');
    });
});
