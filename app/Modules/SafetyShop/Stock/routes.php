<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Stock\Http\Controllers\StockController;
Route::prefix('stock')->name('stock.')->group(function () {
    Route::get('/',[StockController::class,'index'])->name('index');
    Route::get('/create',[StockController::class,'create'])->middleware('shop_operator')->name('create');
    Route::get('/{movement}/attachment',[StockController::class,'attachment'])->name('attachment');
    Route::post('/',[StockController::class,'store'])->middleware('shop_operator')->name('store');
});
