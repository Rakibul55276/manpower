<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Customers\Http\Controllers\CustomerController;
Route::prefix('customers')->name('customers.')->group(function(){
    Route::get('/',[CustomerController::class,'index'])->name('index');
    Route::middleware('approver')->group(function(){
        Route::get('/create',[CustomerController::class,'create'])->name('create');
        Route::post('/',[CustomerController::class,'store'])->name('store');
        Route::get('/{customer}/edit',[CustomerController::class,'edit'])->name('edit');
        Route::put('/{customer}',[CustomerController::class,'update'])->name('update');
    });
});
