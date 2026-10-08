<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SPMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->user()->isSP()) {
            return $next($request);
        }

        return redirect('/');
    }
}
