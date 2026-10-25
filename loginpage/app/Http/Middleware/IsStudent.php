<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsStudent
{
    public function handle(Request $request, Closure $next)
    {
        if (session('role') !== 'student') {
            return redirect()->route('login');
        }
        return $next($request);
    }
}