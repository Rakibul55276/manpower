<?php

use App\Http\Controllers\CommercialDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'auth.session', 'saas.subscription', 'operational'])->group(function () {
    foreach (['quotation' => 'quotations', 'delivery_note' => 'delivery-notes'] as $type => $prefix) {
        Route::prefix($prefix)->name($prefix.'.')->group(function () use ($type) {
            Route::get('/', [CommercialDocumentController::class, 'index'])->defaults('document_type', $type)->name('index');
            Route::get('/create', [CommercialDocumentController::class, 'create'])->defaults('document_type', $type)->middleware('shop_operator')->name('create');
            Route::post('/', [CommercialDocumentController::class, 'store'])->defaults('document_type', $type)->middleware('shop_operator')->name('store');
            Route::get('/{document}', [CommercialDocumentController::class, 'show'])->defaults('document_type', $type)->whereNumber('document')->name('show');
            Route::post('/{document}/approve', [CommercialDocumentController::class, 'approve'])->defaults('document_type', $type)->middleware('approver')->whereNumber('document')->name('approve');
            Route::get('/{document}/pdf', [CommercialDocumentController::class, 'pdf'])->defaults('document_type', $type)->whereNumber('document')->name('pdf');
        });
    }
});
