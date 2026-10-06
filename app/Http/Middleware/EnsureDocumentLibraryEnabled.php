<?php

namespace App\Http\Middleware;

use Closure;

class EnsureDocumentLibraryEnabled
{
    public function handle($request, Closure $next)
    {
        abort_unless(config('document_library.enabled'), 404);

        return $next($request);
    }
}
