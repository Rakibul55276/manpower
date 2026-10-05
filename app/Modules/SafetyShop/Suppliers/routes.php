<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Suppliers\Http\Controllers\SupplierController;
Route::prefix('suppliers')->name('suppliers.')->group(function () {
    Route::get('/',[SupplierController::class,'index'])->name('index');
    Route::get('/create',[SupplierController::class,'create'])->middleware('approver')->name('create');
    Route::get('/{master}/edit',[SupplierController::class,'edit'])->middleware('approver')->name('edit');
    Route::post('/',[SupplierController::class,'saveMaster'])->middleware('approver')->name('store');
    Route::put('/{master}',[SupplierController::class,'saveMaster'])->middleware('approver')->name('update');
});
