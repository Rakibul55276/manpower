<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Sales\Http\Controllers\SaleController;
use App\Modules\SafetyShop\Sales\Http\Controllers\ReturnController;
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/',[SaleController::class,'index'])->name('index');
    Route::get('/checkout',[SaleController::class,'create'])->middleware('shop_operator')->name('create');
    Route::post('/',[SaleController::class,'store'])->middleware('shop_operator')->name('store');
    Route::get('/{sale}/draft',[SaleController::class,'editDraft'])->middleware('shop_operator')->whereNumber('sale')->name('draft');
    Route::post('/{sale}/payments',[SaleController::class,'recordPayment'])->middleware('shop_operator')->whereNumber('sale')->name('payments.store');
    Route::post('/{sale}/payments/{payment}/reverse',[SaleController::class,'reversePayment'])->middleware('shop_operator')->whereNumber('sale')->whereNumber('payment')->name('payments.reverse');
    Route::post('/{sale}/cancel-replace',[SaleController::class,'cancelAndReplace'])->middleware('shop_operator')->whereNumber('sale')->name('cancel-replace');
    Route::get('/{sale}/pdf',[SaleController::class,'pdf'])->whereNumber('sale')->name('pdf');
    Route::get('/{sale}',[SaleController::class,'show'])->whereNumber('sale')->name('show');
});
Route::get('/sales/barcode',[SaleController::class,'barcode'])->middleware('shop_operator')->name('barcode');
Route::prefix('returns')->name('returns.')->group(function () {
    Route::get('/',[ReturnController::class,'index'])->name('index');
    Route::get('/new',[ReturnController::class,'create'])->middleware('shop_operator')->name('create');
    Route::get('/lookup',[ReturnController::class,'lookup'])->middleware('shop_operator')->name('lookup');
    Route::post('/',[ReturnController::class,'store'])->middleware('shop_operator')->name('store');
    Route::get('/{return}/pdf',[ReturnController::class,'pdf'])->whereNumber('return')->name('pdf');
    Route::get('/{return}',[ReturnController::class,'show'])->whereNumber('return')->name('show');
});
