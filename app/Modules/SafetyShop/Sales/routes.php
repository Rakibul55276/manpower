<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Sales\Http\Controllers\SaleController;
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/',[SaleController::class,'index'])->name('index');
    Route::get('/checkout',[SaleController::class,'create'])->middleware('approver')->name('create');
    Route::post('/',[SaleController::class,'store'])->middleware('approver')->name('store');
    Route::get('/{sale}',[SaleController::class,'show'])->whereNumber('sale')->name('show');
});
Route::get('/sales/barcode',[SaleController::class,'barcode'])->middleware('approver')->name('barcode');
