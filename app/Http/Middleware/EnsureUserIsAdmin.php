<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        if(!(Auth::user()->admin && Auth::user()->admin->status)) {

            return response()->json([
                'status' => false,
                'message' => 'INVALID_ACCESS',
                'token' => null
            ], 400);
        }

        return $next($request);
    }
}
