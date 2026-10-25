<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLoggedIn
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('login_id')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}