<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Customers\Http\Controllers\CustomerController;
Route::prefix('customers')->name('customers.')->group(function(){
    Route::get('/',[CustomerController::class,'index'])->name('index');
    Route::middleware('shop_operator')->group(function(){
        Route::get('/create',[CustomerController::class,'create'])->name('create');
        Route::post('/',[CustomerController::class,'store'])->name('store');
        Route::get('/{customer}/documents/{document}',[CustomerController::class,'document'])->where('document','cr|vat|national-address')->name('documents.show');
    });
    Route::middleware('company_admin')->group(function(){
        Route::get('/{customer}/edit',[CustomerController::class,'edit'])->name('edit');
        Route::put('/{customer}',[CustomerController::class,'update'])->name('update');
        Route::post('/{customer}/approve',[CustomerController::class,'approve'])->name('approve');
        Route::delete('/{customer}',[CustomerController::class,'destroy'])->name('destroy');
    });
});
