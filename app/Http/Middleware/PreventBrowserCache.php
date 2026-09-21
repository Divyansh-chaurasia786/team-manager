<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBrowserCache
{
    /**
     * Prevent browsers from caching dynamic views, ensuring all redeployments
     * and live changes are immediately visible upon page refresh.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Do NOT tamper with static uploads, binary files, or responses that explicitly define caching
        if ($request->is('uploads/*') || $request->is('images/*') || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return $response;
        }

        if ($response->headers->hasCacheControlDirective('public') || $response->headers->hasCacheControlDirective('immutable')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
