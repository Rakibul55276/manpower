<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Products\Http\Controllers\ProductController;
Route::middleware(['auth','active','auth.session',\App\Http\Middleware\EnsureSafetyShopEnabled::class])->prefix('safety-shop')->name('safety-shop.')->group(function () {
    Route::get('/',[ProductController::class,'index'])->name('index');
    foreach (['Products','Categories','Suppliers','Locations','Stock','Sales','Reports'] as $feature) {
        require app_path('Modules/SafetyShop/'.$feature.'/routes.php');
    }
});
