<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (session('role') !== 'admin') {
            return redirect()->route('login');
        }
        return $next($request);
    }
}