<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Home\Http\Controllers\SafetyShopController;
Route::middleware(['auth','active','auth.session','saas.subscription','operational',\App\Http\Middleware\EnsureSafetyShopEnabled::class])->prefix('safety-shop')->name('safety-shop.')->group(function () {
    require app_path('Modules/SafetyShop/Stock/routes.php');
    Route::get('/',[SafetyShopController::class,'index'])->name('index');
    foreach (['Products','Categories','ProductMasters','Suppliers','Locations','Customers','Sales'] as $feature) require app_path('Modules/SafetyShop/'.$feature.'/routes.php');

    // Admins and managers review company analytics; issuers only perform operations.
    Route::middleware('approver')->group(function () {
        require app_path('Modules/SafetyShop/Reports/routes.php');
    });
});
