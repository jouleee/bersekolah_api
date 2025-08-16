<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
        
        // Check if user has any of the required roles
        $userRole = $request->user()->role;
        if (!in_array($userRole, $roles) && !in_array('any', $roles)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have the required role to access this resource.'
            ], 403);
        }
        
        return $next($request);
    }
}