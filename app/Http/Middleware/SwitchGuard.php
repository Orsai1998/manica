<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SwitchGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (in_array($guard, array_keys(config("auth.guards")))) {
            config(["auth.defaults.guard" => $guard]);
        }

        return $next($request);
    }
}
