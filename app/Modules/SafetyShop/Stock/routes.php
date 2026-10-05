<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Stock\Http\Controllers\StockController;
Route::prefix('stock')->name('stock.')->group(function () {
    Route::get('/',[StockController::class,'index'])->name('index');
    Route::get('/create',[StockController::class,'create'])->middleware('approver')->name('create');
    Route::post('/',[StockController::class,'store'])->middleware('approver')->name('store');
});
