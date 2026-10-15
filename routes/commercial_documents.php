<?php

use App\Http\Controllers\CommercialDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription', 'operational', 'module:safety-shop', \App\Http\Middleware\EnsureSafetyShopEnabled::class])->prefix('safety-shop')->group(function () {
    foreach (['quotation' => 'quotations', 'delivery_note' => 'delivery-notes'] as $type => $prefix) {
        Route::prefix($prefix)->name($prefix.'.')->group(function () use ($type) {
            Route::get('/', [CommercialDocumentController::class, 'index'])->defaults('document_type', $type)->name('index');
            Route::get('/create', [CommercialDocumentController::class, 'create'])->defaults('document_type', $type)->middleware('shop_operator')->name('create');
            Route::post('/', [CommercialDocumentController::class, 'store'])->defaults('document_type', $type)->middleware('shop_operator')->name('store');
            if ($type === 'quotation') {
                Route::get('/{document}/edit', [CommercialDocumentController::class, 'edit'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('edit');
                Route::put('/{document}', [CommercialDocumentController::class, 'update'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('update');
                Route::post('/{document}/revise', [CommercialDocumentController::class, 'revise'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('revise');
                Route::post('/{document}/cancel', [CommercialDocumentController::class, 'cancel'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('cancel');
            }
            Route::get('/{document}', [CommercialDocumentController::class, 'show'])->defaults('document_type', $type)->whereNumber('document')->name('show');
            Route::post('/{document}/approve', [CommercialDocumentController::class, 'approve'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('approve');
            Route::get('/{document}/pdf', [CommercialDocumentController::class, 'pdf'])->defaults('document_type', $type)->whereNumber('document')->name('pdf');
        });
    }
});
