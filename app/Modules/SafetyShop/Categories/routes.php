<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Categories\Http\Controllers\CategoryController;
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/',[CategoryController::class,'index'])->name('index');
    Route::get('/create',[CategoryController::class,'create'])->middleware('approver')->name('create');
    Route::get('/{master}/edit',[CategoryController::class,'edit'])->middleware('approver')->name('edit');
    Route::post('/',[CategoryController::class,'saveMaster'])->middleware('approver')->name('store');
    Route::put('/{master}',[CategoryController::class,'saveMaster'])->middleware('approver')->name('update');
});
