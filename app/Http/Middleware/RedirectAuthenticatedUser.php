<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            return match ($user->role) {
                'owner' => redirect()->route('owner.dashboard'),
                'manager' => redirect()->route('manager.dashboard'),
                'tenant' => redirect()->route('tenant.dashboard'),
                default => $next($request),
            };
        }

        return $next($request);
    }
}
