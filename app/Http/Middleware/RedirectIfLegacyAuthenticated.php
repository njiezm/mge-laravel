<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfLegacyAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('user_id')) {
            return session('user_role') === 'admin'
                ? redirect()->route('admin.dashboard')
                : redirect()->route('dashboard');
        }

        return $next($request);
    }
}
