<?php

use App\Modules\DocumentLibrary\Http\Controllers\DocumentLibraryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'auth.session', \App\Http\Middleware\EnsureDocumentLibraryEnabled::class])
    ->prefix('document-library')->name('document-library.')->group(function () {
        Route::get('/', [DocumentLibraryController::class, 'index'])->name('index');
        Route::get('/create', [DocumentLibraryController::class, 'create'])->name('create');
        Route::post('/', [DocumentLibraryController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentLibraryController::class, 'show'])->whereNumber('document')->name('show');
        Route::get('/{document}/edit', [DocumentLibraryController::class, 'edit'])->whereNumber('document')->name('edit');
        Route::put('/{document}', [DocumentLibraryController::class, 'update'])->whereNumber('document')->name('update');
        Route::get('/{document}/view', [DocumentLibraryController::class, 'view'])->whereNumber('document')->name('view');
        Route::get('/{document}/download', [DocumentLibraryController::class, 'download'])->whereNumber('document')->name('download');
        Route::delete('/{document}', [DocumentLibraryController::class, 'destroy'])->middleware('approver')->whereNumber('document')->name('destroy');
    });
