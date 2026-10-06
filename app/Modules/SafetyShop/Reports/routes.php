<?php
use Illuminate\Support\Facades\Route;
use App\Modules\SafetyShop\Reports\Http\Controllers\ReportController;
Route::get('/reports',[ReportController::class,'index'])->name('reports.index');
Route::get('/reports/export.csv',[ReportController::class,'export'])->name('export');
Route::get('/reports/financial.csv',[ReportController::class,'financialCsv'])->name('reports.financial.csv');
Route::get('/reports/financial.pdf',[ReportController::class,'financialPdf'])->name('reports.financial.pdf');
