<?php

use App\Modules\DocumentLibrary\Http\Controllers\DocumentLibraryController;
use Illuminate\Support\Facades\Route;

foreach ([
    ['module' => 'manpower', 'prefix' => 'manpower/document-library', 'name' => 'manpower.documents.'],
    ['module' => 'safety-shop', 'prefix' => 'safety-shop/document-library', 'name' => 'safety-shop.documents.'],
] as $library) {
    Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription', 'operational', 'module:'.$library['module'], \App\Http\Middleware\EnsureDocumentLibraryEnabled::class])
        ->prefix($library['prefix'])->name($library['name'])->group(function () use ($library) {
            $withDefaults = fn ($route) => $route->defaults('document_module', $library['module'])->defaults('document_route_prefix', rtrim($library['name'], '.'));
            $withDefaults(Route::get('/', [DocumentLibraryController::class, 'index']))->name('index');
            $withDefaults(Route::get('/create', [DocumentLibraryController::class, 'create']))->name('create');
            $withDefaults(Route::post('/', [DocumentLibraryController::class, 'store']))->name('store');
            $withDefaults(Route::get('/{document}', [DocumentLibraryController::class, 'show'])->whereNumber('document'))->name('show');
            $withDefaults(Route::get('/{document}/edit', [DocumentLibraryController::class, 'edit'])->whereNumber('document'))->name('edit');
            $withDefaults(Route::put('/{document}', [DocumentLibraryController::class, 'update'])->whereNumber('document'))->name('update');
            $withDefaults(Route::get('/{document}/view', [DocumentLibraryController::class, 'view'])->whereNumber('document'))->name('view');
            $withDefaults(Route::get('/{document}/download', [DocumentLibraryController::class, 'download'])->whereNumber('document'))->name('download');
            $withDefaults(Route::delete('/{document}', [DocumentLibraryController::class, 'destroy'])->middleware('company_admin')->whereNumber('document'))->name('destroy');
        });
}
