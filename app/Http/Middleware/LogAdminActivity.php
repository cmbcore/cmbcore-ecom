<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ActivityLog\Services\ActivityLogService;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/admin/*')) {
            return $response;
        }

        if (! in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        if (($request->attributes->get('admin_activity_context', [])['skip'] ?? false) === true) {
            return $response;
        }

        app(ActivityLogService::class)->logRequest($request, $response);

        return $response;
    }
}
