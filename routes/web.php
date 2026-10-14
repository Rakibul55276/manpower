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
    Route::get('/login', function () { return redirect()->route('manpower.login'); })->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->defaults('module','manpower')->middleware('throttle:5,1')->name('login.submit');
    Route::get('/manpower/login', [\App\Http\Controllers\AuthController::class, 'form'])->defaults('module','manpower')->name('manpower.login');
    Route::post('/manpower/login', [\App\Http\Controllers\AuthController::class, 'login'])->defaults('module','manpower')->middleware('throttle:5,1')->name('manpower.login.submit');
    Route::get('/safety-shop/login', [\App\Http\Controllers\AuthController::class, 'form'])->defaults('module','safety-shop')->name('safety-shop.login');
    Route::post('/safety-shop/login', [\App\Http\Controllers\AuthController::class, 'login'])->defaults('module','safety-shop')->middleware('throttle:5,1')->name('safety-shop.login.submit');
});
Route::get('/superadmin/login', [\App\Http\Controllers\SuperAdminAuthController::class, 'form'])->name('superadmin.login');
Route::post('/superadmin/login', [\App\Http\Controllers\SuperAdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('superadmin.login.submit');
Route::get('/superadmin', function () {
    if (!auth()->check()) return redirect()->route('superadmin.login');
    abort_unless(auth()->user()->isSuperAdmin(),403);
    return redirect()->route('companies.index');
})->name('superadmin.index');
Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription'])->group(function () {
    Route::get('/', function () { return redirect()->route(session('active_module') === 'safety-shop' ? 'safety-shop.index' : 'manpower.index'); })->middleware('operational')->name('dashboard');
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
    Route::post('/manpower/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->defaults('module','manpower')->name('manpower.logout');
    Route::post('/safety-shop/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->defaults('module','safety-shop')->name('safety-shop.logout');
    Route::get('/profile', [\App\Http\Controllers\AuthController::class, 'profile'])->name('profile');
    Route::put('/profile/password', [\App\Http\Controllers\AuthController::class, 'password'])->name('profile.password');
    Route::get('/company-logos/{company}', [\App\Http\Controllers\LookupController::class, 'logo'])->name('companies.logo');
    Route::middleware(['operational','approver'])->group(function () {
        Route::get('/audit-report', [\App\Http\Controllers\AuditReportController::class, 'index'])->name('audit.index');
        Route::get('/audit-report/export.csv', [\App\Http\Controllers\AuditReportController::class, 'csv'])->name('audit.csv');
        Route::get('/audit-report/export.pdf', [\App\Http\Controllers\AuditReportController::class, 'pdf'])->name('audit.pdf');
    });
    Route::prefix('company')->name('company-users.')->middleware('company_admin')->group(function () {
        Route::resource('team', \App\Http\Controllers\UserController::class)->except(['show'])->parameters(['team'=>'user'])->names(['index'=>'index','create'=>'create','store'=>'store','edit'=>'edit','update'=>'update','destroy'=>'destroy']);
    });
    Route::prefix('superadmin')->middleware('super_admin')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\SuperAdminAuthController::class, 'logout'])->name('superadmin.logout');
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show']);
        foreach (['companies'] as $lookup) {
            $controller = \App\Http\Controllers\LookupController::class;
            Route::get('/'.$lookup, [$controller, 'index'])->defaults('lookup', $lookup)->name($lookup.'.index');
            Route::get('/'.$lookup.'/create', [$controller, 'create'])->defaults('lookup', $lookup)->name($lookup.'.create');
            Route::post('/'.$lookup, [$controller, 'store'])->defaults('lookup', $lookup)->name($lookup.'.store');
            Route::get('/'.$lookup.'/{id}/edit', [$controller, 'edit'])->defaults('lookup', $lookup)->name($lookup.'.edit');
            Route::put('/'.$lookup.'/{id}', [$controller, 'update'])->defaults('lookup', $lookup)->name($lookup.'.update');
            Route::delete('/'.$lookup.'/{id}', [$controller, 'destroy'])->defaults('lookup', $lookup)->name($lookup.'.destroy');
        }
        Route::resource('companies.branches', \App\Http\Controllers\BranchController::class)->except(['show']);
        Route::get('/saas/vouchers', [\App\Http\Controllers\SaasVoucherController::class, 'index'])->name('saas.vouchers.index');
        Route::post('/saas/vouchers', [\App\Http\Controllers\SaasVoucherController::class, 'store'])->name('saas.vouchers.store');
        Route::put('/saas/companies/{company}/status', [\App\Http\Controllers\SaasVoucherController::class, 'status'])->name('saas.companies.status');
    });
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscription.show');
    Route::post('/subscription/redeem', [\App\Http\Controllers\SubscriptionController::class, 'redeem'])->name('subscription.redeem');
    Route::prefix('invoicing')->name('invoicing.')->middleware(['operational', 'super_admin', \App\Http\Middleware\EnsureInvoicingEnabled::class])->group(function () {
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
        Route::get('/design', [$controller, 'designPage'])->name('design.index');
        Route::put('/design', [$controller, 'design'])->name('design.update');
        Route::post('/customers', [$controller, 'customer'])->middleware('approver')->name('customers.store');
        Route::post('/items', [$controller, 'item'])->middleware('approver')->name('items.store');
        Route::put('/settings', [$controller, 'settings'])->middleware('approver')->name('settings.update');
        Route::get('/{invoice}', [$controller, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [$controller, 'pdf'])->name('pdf');
        Route::get('/{invoice}/xml', [$controller, 'xml'])->name('xml');
        Route::post('/{invoice}/approve', [$controller, 'approve'])->middleware('approver')->name('approve');
        Route::post('/{invoice}/paid', [$controller, 'paid'])->middleware('approver')->name('paid');
        Route::delete('/{invoice}', [$controller, 'destroy'])->name('destroy');
    });
    Route::get('/zatca', [\App\Modules\Zatca\Http\Controllers\ZatcaController::class, 'index'])->middleware(['operational', 'super_admin', \App\Http\Middleware\EnsureZatcaEnabled::class])->name('zatca.index');
});

require __DIR__.'/safety_shop.php';
require __DIR__.'/manpower.php';
require __DIR__.'/document_library.php';
require __DIR__.'/commercial_documents.php';
require __DIR__.'/manpower_legacy.php';
