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
        Route::delete('/'.$prefix.'/{employee}', [$controller, 'destroy'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
    }
    foreach (['companies', 'designations'] as $lookup) {
        $controller = \App\Http\Controllers\LookupController::class;
        Route::get('/'.$lookup, [$controller, 'index'])->defaults('lookup', $lookup)->name($lookup.'.index');
        Route::get('/'.$lookup.'/create', [$controller, 'create'])->defaults('lookup', $lookup)->name($lookup.'.create');
        Route::post('/'.$lookup, [$controller, 'store'])->defaults('lookup', $lookup)->name($lookup.'.store');
        Route::get('/'.$lookup.'/{id}/edit', [$controller, 'edit'])->defaults('lookup', $lookup)->name($lookup.'.edit');
        Route::put('/'.$lookup.'/{id}', [$controller, 'update'])->defaults('lookup', $lookup)->name($lookup.'.update');
        Route::delete('/'.$lookup.'/{id}', [$controller, 'destroy'])->middleware('super_admin')->defaults('lookup', $lookup)->name($lookup.'.destroy');
    }
    foreach (['rental' => 'timesheets', 'own' => 'attendance'] as $workforce => $prefix) {
        $controller = \App\Http\Controllers\TimesheetController::class;
        Route::get('/'.$prefix.'/bulk', [$controller, 'bulk'])->defaults('workforce', $workforce)->name($prefix.'.bulk');
        Route::post('/'.$prefix.'/bulk', [$controller, 'storeBulk'])->defaults('workforce', $workforce)->name($prefix.'.bulk.store');
        Route::post('/'.$prefix.'/bulk-approve', [$controller, 'bulkApprove'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.bulk.approve');
        Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
        Route::get('/'.$prefix.'/export/pdf', [$controller, 'pdf'])->defaults('workforce', $workforce)->name($prefix.'.pdf');
        Route::get('/'.$prefix.'/employee/{employee}/timesheet-pdf', [$controller, 'employeePdfForm'])->defaults('workforce', $workforce)->name($prefix.'.employee.pdf.form');
        Route::post('/'.$prefix.'/employee/{employee}/timesheet-pdf', [$controller, 'employeePdf'])->defaults('workforce', $workforce)->name($prefix.'.employee.pdf');
        Route::get('/'.$prefix.'/create', [$controller, 'create'])->defaults('workforce', $workforce)->name($prefix.'.create');
        Route::post('/'.$prefix, [$controller, 'store'])->defaults('workforce', $workforce)->name($prefix.'.store');
        Route::get('/'.$prefix.'/{timesheet}/edit', [$controller, 'edit'])->defaults('workforce', $workforce)->name($prefix.'.edit');
        Route::put('/'.$prefix.'/{timesheet}', [$controller, 'update'])->defaults('workforce', $workforce)->name($prefix.'.update');
        Route::delete('/'.$prefix.'/{timesheet}', [$controller, 'destroy'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
        Route::post('/'.$prefix.'/{timesheet}/review', [$controller, 'review'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.review');
    }
    foreach (['rental' => 'payrolls', 'own' => 'salaries'] as $workforce => $prefix) {
        $controller = \App\Http\Controllers\PayrollController::class;
        Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
        Route::get('/'.$prefix.'/export', [$controller, 'export'])->defaults('workforce', $workforce)->name($prefix.'.export');
        Route::post('/'.$prefix.'/bulk-approve', [$controller, 'bulkApprove'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.bulk.approve');
        Route::get('/'.$prefix.'/{payroll}/pdf', [$controller, 'pdf'])->defaults('workforce', $workforce)->name($prefix.'.pdf');
        Route::get('/'.$prefix.'/{payroll}', [$controller, 'show'])->defaults('workforce', $workforce)->name($prefix.'.show');
        Route::post('/'.$prefix, [$controller, 'store'])->defaults('workforce', $workforce)->name($prefix.'.store');
        Route::put('/'.$prefix.'/{payroll}', [$controller, 'update'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.update');
        Route::post('/'.$prefix.'/{payroll}/paid', [$controller, 'paid'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.paid');
        Route::post('/'.$prefix.'/{payroll}/approve', [$controller, 'approve'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.approve');
        Route::delete('/'.$prefix.'/{payroll}', [$controller, 'destroy'])->middleware('super_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
    }
    Route::middleware('approver')->group(function () {
        Route::get('/audit-report', [\App\Http\Controllers\AuditReportController::class, 'index'])->name('audit.index');
        Route::get('/audit-report/export.csv', [\App\Http\Controllers\AuditReportController::class, 'csv'])->name('audit.csv');
        Route::get('/audit-report/export.pdf', [\App\Http\Controllers\AuditReportController::class, 'pdf'])->name('audit.pdf');
    });
    Route::middleware('super_admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show', 'destroy']);
        Route::get('/document-branding', [\App\Modules\SafetyShop\Shared\Http\Controllers\ReceiptSettingController::class, 'edit'])->name('document-branding.edit');
        Route::put('/document-branding', [\App\Modules\SafetyShop\Shared\Http\Controllers\ReceiptSettingController::class, 'update'])->name('document-branding.update');
    });
    Route::prefix('invoicing')->name('invoicing.')->middleware(\App\Http\Middleware\EnsureInvoicingEnabled::class)->group(function () {
        $controller = \App\Modules\Invoicing\Http\Controllers\InvoiceController::class;
        Route::get('/', [$controller, 'index'])->name('index');
        Route::get('/create', [$controller, 'create'])->name('create');
        Route::post('/', [$controller, 'store'])->name('store');
        Route::get('/masters', [$controller, 'masters'])->name('masters');
        Route::get('/customers', [$controller, 'customers'])->name('customers.index');
        Route::get('/customers/{customer}/edit', [$controller, 'editCustomer'])->middleware('approver')->name('customers.edit');
        Route::put('/customers/{customer}', [$controller, 'updateCustomer'])->middleware('approver')->name('customers.update');
        Route::get('/products-services', [$controller, 'items'])->name('items.index');
        Route::get('/settings', [$controller, 'settingsPage'])->name('settings.index');
        Route::get('/design', [$controller, 'designPage'])->middleware('super_admin')->name('design.index');
        Route::put('/design', [$controller, 'design'])->middleware('super_admin')->name('design.update');
        Route::post('/customers', [$controller, 'customer'])->middleware('approver')->name('customers.store');
        Route::post('/items', [$controller, 'item'])->middleware('approver')->name('items.store');
        Route::put('/settings', [$controller, 'settings'])->middleware('approver')->name('settings.update');
        Route::get('/{invoice}', [$controller, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [$controller, 'pdf'])->name('pdf');
        Route::get('/{invoice}/xml', [$controller, 'xml'])->name('xml');
        Route::post('/{invoice}/approve', [$controller, 'approve'])->middleware('approver')->name('approve');
        Route::post('/{invoice}/paid', [$controller, 'paid'])->middleware('approver')->name('paid');
        Route::delete('/{invoice}', [$controller, 'destroy'])->middleware('super_admin')->name('destroy');
    });
    Route::get('/zatca', [\App\Modules\Zatca\Http\Controllers\ZatcaController::class, 'index'])->middleware(\App\Http\Middleware\EnsureZatcaEnabled::class)->name('zatca.index');
});

require __DIR__.'/safety_shop.php';
require __DIR__.'/document_library.php';
