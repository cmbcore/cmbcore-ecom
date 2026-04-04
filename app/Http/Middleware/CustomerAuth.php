<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuth
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => __('frontend.account.messages.unauthenticated')], 401)
                : redirect()->route('storefront.account.login');
        }

        if (! in_array($user->role, ['customer', 'admin'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
