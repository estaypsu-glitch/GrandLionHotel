<?php

namespace App\Http\Middleware;

use App\Support\AccountDirectory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (array_keys(AccountDirectory::guardModelMap()) as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);
                break;
            }
        }

        return $next($request);
    }
}
