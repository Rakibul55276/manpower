<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;

class AuditTransactions
{
    public function handle(Request $request, Closure $next)
    {
        $userId = optional($request->user())->id;
        $lastLogId = ActivityLog::max('id') ?? 0;
        $response = $next($request);

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $userId = $userId ?: optional($request->user())->id;
        if (!$userId || $response->getStatusCode() >= 400 || session()->has('errors')) {
            return $response;
        }

        // Controllers can write a more specific audit entry. Add a safe fallback
        // only when the completed transaction did not already create one.
        if (!ActivityLog::where('id', '>', $lastLogId)->where('user_id', $userId)->exists()) {
            $route = $request->route();
            $routeName = $route ? $route->getName() : null;
            $parameters = collect($route ? $route->parameters() : [])->map(function ($value, $key) {
                $id = is_object($value) && method_exists($value, 'getKey') ? $value->getKey() : $value;
                return is_scalar($id) ? $key.' #'.$id : null;
            })->filter()->implode(' · ');

            ActivityLog::create([
                'user_id' => $userId,
                'action' => $routeName ? ucwords(str_replace(['.', '-'], ' ', $routeName)) : $request->method().' transaction',
                'subject' => $parameters ?: $request->method().' '.$request->path(),
            ]);
        }

        return $response;
    }
}
