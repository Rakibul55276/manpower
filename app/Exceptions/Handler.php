<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            if ($request->is('manpower/login')) return redirect()->route('manpower.login')->withErrors(['session' => 'Your login session expired. Please sign in again.']);
            if ($request->is('safety-shop/login')) return redirect()->route('safety-shop.login')->withErrors(['session' => 'Your login session expired. Please sign in again.']);
            if ($request->is('superadmin/login')) return redirect()->route('superadmin.login')->withErrors(['session' => 'Your login session expired. Please sign in again.']);
        }
        return parent::render($request, $exception);
    }
}
