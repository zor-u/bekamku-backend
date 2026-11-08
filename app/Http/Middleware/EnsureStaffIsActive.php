<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user();

        if ($staff && ! $staff->is_active) {
            $staff->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        return $next($request);
    }
}
