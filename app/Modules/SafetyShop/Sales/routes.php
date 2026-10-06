<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Sales\Http\Controllers\SaleController;
use App\Modules\SafetyShop\Sales\Http\Controllers\ReturnController;
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/',[SaleController::class,'index'])->name('index');
    Route::get('/checkout',[SaleController::class,'create'])->middleware('approver')->name('create');
    Route::post('/',[SaleController::class,'store'])->middleware('approver')->name('store');
    Route::get('/{sale}',[SaleController::class,'show'])->whereNumber('sale')->name('show');
});
Route::get('/sales/barcode',[SaleController::class,'barcode'])->middleware('approver')->name('barcode');
Route::prefix('returns')->name('returns.')->group(function () {
    Route::get('/',[ReturnController::class,'index'])->name('index');
    Route::get('/new',[ReturnController::class,'create'])->middleware('approver')->name('create');
    Route::get('/lookup',[ReturnController::class,'lookup'])->middleware('approver')->name('lookup');
    Route::post('/',[ReturnController::class,'store'])->middleware('approver')->name('store');
    Route::get('/{return}',[ReturnController::class,'show'])->whereNumber('return')->name('show');
});
