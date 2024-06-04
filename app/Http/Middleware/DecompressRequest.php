<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecompressRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->getMethod() == 'POST' && $request->headers->get('Content-Encoding') == 'gzip') {
            $content = gzdecode($request->getContent());
            if ($content === false) {
                return response('Invalid GZIP content', 400);
            }

            // Replace the request content with the decompressed content
            // this assumes that the decompressed data is JSON. If the data format can vary, we might need additional logic
            $request->replace(json_decode($content, true));
        }

        return $next($request);
    }
}
