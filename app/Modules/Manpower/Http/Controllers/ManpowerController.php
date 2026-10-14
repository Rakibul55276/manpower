<?php

namespace App\Modules\Manpower\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;

class ManpowerController extends Controller
{
    public function index(Request $request)
    {
        return app(DashboardController::class)->index($request);
    }
}
