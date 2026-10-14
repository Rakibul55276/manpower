<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription', 'operational', 'module:manpower', \App\Http\Middleware\EnsureManpowerEnabled::class])
    ->prefix('manpower')->group(function () {
        Route::get('/', [\App\Modules\Manpower\Http\Controllers\ManpowerController::class, 'index'])->name('manpower.index');
        Route::get('/employee-photos/{employee}', [\App\Http\Controllers\EmployeeController::class, 'photo'])->name('employees.photo');
        Route::post('/employees/{employee}/advances', [\App\Http\Controllers\EmployeeAdvanceController::class, 'store'])->name('employees.advances.store');

        foreach (['rental' => 'employees', 'own' => 'own-employees'] as $workforce => $prefix) {
            $controller = \App\Http\Controllers\EmployeeController::class;
            Route::get('/'.$prefix, [$controller, 'index'])->defaults('workforce', $workforce)->name($prefix.'.index');
            Route::get('/'.$prefix.'/create', [$controller, 'create'])->defaults('workforce', $workforce)->name($prefix.'.create');
            Route::get('/'.$prefix.'/{employee}/cv', [$controller, 'cv'])->defaults('workforce', $workforce)->name($prefix.'.cv');
            Route::post('/'.$prefix, [$controller, 'store'])->defaults('workforce', $workforce)->name($prefix.'.store');
            Route::get('/'.$prefix.'/{employee}', [$controller, 'show'])->defaults('workforce', $workforce)->name($prefix.'.show');
            Route::get('/'.$prefix.'/{employee}/edit', [$controller, 'edit'])->defaults('workforce', $workforce)->name($prefix.'.edit');
            Route::put('/'.$prefix.'/{employee}', [$controller, 'update'])->defaults('workforce', $workforce)->name($prefix.'.update');
            Route::delete('/'.$prefix.'/{employee}', [$controller, 'destroy'])->middleware('company_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
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
            Route::get('/'.$prefix.'/{timesheet}/edit', [$controller, 'edit'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.edit');
            Route::put('/'.$prefix.'/{timesheet}', [$controller, 'update'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.update');
            Route::delete('/'.$prefix.'/{timesheet}', [$controller, 'destroy'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.destroy');
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
            Route::put('/'.$prefix.'/{payroll}', [$controller, 'update'])->middleware('company_admin')->defaults('workforce', $workforce)->name($prefix.'.update');
            Route::post('/'.$prefix.'/{payroll}/paid', [$controller, 'paid'])->middleware('company_admin')->defaults('workforce', $workforce)->name($prefix.'.paid');
            Route::post('/'.$prefix.'/{payroll}/approve', [$controller, 'approve'])->middleware('approver')->defaults('workforce', $workforce)->name($prefix.'.approve');
            Route::delete('/'.$prefix.'/{payroll}', [$controller, 'destroy'])->middleware('company_admin')->defaults('workforce', $workforce)->name($prefix.'.destroy');
        }

        Route::prefix('company')->middleware('company_admin')->group(function () {
            $controller = \App\Http\Controllers\LookupController::class;
            Route::get('/designations', [$controller, 'index'])->defaults('lookup', 'designations')->name('designations.index');
            Route::get('/designations/create', [$controller, 'create'])->defaults('lookup', 'designations')->name('designations.create');
            Route::post('/designations', [$controller, 'store'])->defaults('lookup', 'designations')->name('designations.store');
            Route::get('/designations/{id}/edit', [$controller, 'edit'])->defaults('lookup', 'designations')->name('designations.edit');
            Route::put('/designations/{id}', [$controller, 'update'])->defaults('lookup', 'designations')->name('designations.update');
            Route::delete('/designations/{id}', [$controller, 'destroy'])->defaults('lookup', 'designations')->name('designations.destroy');
        });
    });
