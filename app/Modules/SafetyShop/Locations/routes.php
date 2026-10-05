<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Locations\Http\Controllers\LocationController;
Route::prefix('locations')->name('locations.')->group(function () {
    Route::get('/',[LocationController::class,'index'])->name('index');
    Route::get('/create',[LocationController::class,'create'])->middleware('approver')->name('create');
    Route::get('/{master}/edit',[LocationController::class,'edit'])->middleware('approver')->name('edit');
    Route::post('/',[LocationController::class,'saveMaster'])->middleware('approver')->name('store');
    Route::put('/{master}',[LocationController::class,'saveMaster'])->middleware('approver')->name('update');
});
