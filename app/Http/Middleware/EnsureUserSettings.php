<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSettings
{
    /**
     * Seed default user preferences if they don't exist yet.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->ensureSettings();
        $request->user()?->ensureWorkSchedule();

        return $next($request);
    }
}
