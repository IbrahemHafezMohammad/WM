<?php

namespace App\Http\Middleware;

use App\Models\WhitelistIP;
use App\Services\WebService\WebRequestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WhitelistIPMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get client's IP address
        $webrequestservice = new WebRequestService($request);
        $clientIP = $webrequestservice->getIpAddress();
        // Retrieve whitelisted IPs from the database
        $whitelistedIPs = WhitelistIP::all()->pluck('ip')->toArray();
        // Check if the client's IP is in the whitelist
        if (in_array($clientIP, $whitelistedIPs)|| (Auth::check() && Auth::user()->can('Access from any IP')))
        {
            return $next($request);
        }
        return response('Unauthorized. Your IP is not whitelisted.', 401);
        // If the IP is whitelisted, proceed with the request
    }
}
