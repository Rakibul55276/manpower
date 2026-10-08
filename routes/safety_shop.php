<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Home\Http\Controllers\SafetyShopController;
Route::middleware(['auth','active','auth.session',\App\Http\Middleware\EnsureSafetyShopEnabled::class])->prefix('safety-shop')->name('safety-shop.')->group(function () {
    Route::middleware('approver')->group(function(){require app_path('Modules/SafetyShop/Stock/routes.php');});
    Route::middleware('super_admin')->group(function () {
        Route::get('/',[SafetyShopController::class,'index'])->name('index');
        foreach (['Products','Categories','ProductMasters','Suppliers','Locations','Customers','Sales','Reports'] as $feature) require app_path('Modules/SafetyShop/'.$feature.'/routes.php');
    });
});
