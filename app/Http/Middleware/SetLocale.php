<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $locale = Auth::user()->locale ?? session('locale', config('app.locale'));
        } else {
            $locale = session('locale', config('app.locale'));
        }

        App::setLocale($locale);

        return $next($request);
    }
}
