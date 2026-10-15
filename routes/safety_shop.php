<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Home\Http\Controllers\SafetyShopController;
Route::middleware(['auth','active','auth.session','saas.subscription','operational','module:safety-shop',\App\Http\Middleware\EnsureSafetyShopEnabled::class])->prefix('safety-shop')->name('safety-shop.')->group(function () {
    require app_path('Modules/SafetyShop/Stock/routes.php');
    Route::get('/',[SafetyShopController::class,'index'])->name('index');
    foreach (['Products','Categories','ProductMasters','Suppliers','Locations','Customers','Sales'] as $feature) require app_path('Modules/SafetyShop/'.$feature.'/routes.php');

    // Admins and managers review company analytics; issuers only perform operations.
    Route::middleware('approver')->group(function () {
        require app_path('Modules/SafetyShop/Reports/routes.php');
        Route::prefix('zatca')->name('zatca.')->middleware(\App\Http\Middleware\EnsureZatcaEnabled::class)->group(function(){
            Route::get('/',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'safetyShop'])->name('index');
            Route::post('/sales/{sale}/prepare',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'prepare'])->whereNumber('sale')->name('prepare');
            Route::post('/returns/{return}/prepare',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'prepareReturn'])->whereNumber('return')->name('prepare-return');
            Route::get('/documents/{document}/xml',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'xml'])->whereNumber('document')->name('xml');
            Route::get('/audit.csv',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'csv'])->name('csv');
            Route::put('/device',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'configure'])->name('configure');
            Route::post('/device/csr',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'generateCsr'])->name('csr');
            Route::post('/device/compliance-csid',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'complianceCsid'])->name('compliance-csid');
            Route::post('/device/production-csid',[\App\Modules\Zatca\Http\Controllers\ZatcaController::class,'productionCsid'])->name('production-csid');
        });
    });
});
