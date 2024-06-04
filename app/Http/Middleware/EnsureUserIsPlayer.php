<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsPlayer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!(Auth::user()->player && Auth::user()->player->active)) {

            return response()->json([
                'status' => false,
                'message' => 'INVALID_ACCESS',
                'token' => null
            ], 400);
        }

        return $next($request);
    }
}
