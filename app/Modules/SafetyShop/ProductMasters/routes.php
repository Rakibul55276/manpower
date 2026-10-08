<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\ProductMasters\Http\Controllers\BrandController;
use App\Modules\SafetyShop\ProductMasters\Http\Controllers\SizeController;
use App\Modules\SafetyShop\ProductMasters\Http\Controllers\UnitController;
use App\Modules\SafetyShop\ProductMasters\Http\Controllers\SafetyStandardController;

foreach (['brands'=>BrandController::class,'sizes'=>SizeController::class,'units'=>UnitController::class,'safety-standards'=>SafetyStandardController::class] as $directory=>$controller) {
    Route::prefix($directory)->name($directory.'.')->group(function () use ($controller) {
        Route::get('/',[$controller,'index'])->name('index');
        Route::middleware('approver')->group(function () use ($controller) {
            Route::get('/create',[$controller,'create'])->name('create');
            Route::get('/{master}/edit',[$controller,'edit'])->name('edit');
            Route::post('/',[$controller,'saveMaster'])->name('store');
            Route::put('/{master}',[$controller,'saveMaster'])->name('update');
        });
    });
}
