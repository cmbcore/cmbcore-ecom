<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
            }

            return redirect()->route('admin.login');
        }

        if (! method_exists($user, 'canAccessAdminPanel') || ! $user->canAccessAdminPanel()) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access the admin panel.');
        }

        return $next($request);
    }
}