<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user();

        if (! $staff?->isAdmin()) {
            return response()->json([
                'message' => 'Administrator access is required.',
            ], 403);
        }

        return $next($request);
    }
}
