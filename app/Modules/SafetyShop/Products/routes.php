<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Products\Http\Controllers\ProductController;
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/',[ProductController::class,'index'])->name('index');
    Route::middleware('approver')->group(function () {
        Route::get('/barcode-lookup',[ProductController::class,'barcodeLookup'])->name('barcode-lookup');
        Route::get('/sku-suggestion',[ProductController::class,'skuSuggestion'])->name('sku-suggestion');
        Route::get('/create',[ProductController::class,'create'])->name('create');
        Route::post('/',[ProductController::class,'store'])->name('store');
        Route::get('/{product}/edit',[ProductController::class,'edit'])->name('edit');
        Route::put('/{product}',[ProductController::class,'update'])->name('update');
    });
    Route::get('/{product}',[ProductController::class,'show'])->whereNumber('product')->name('show');
});
