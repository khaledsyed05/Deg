<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueIsRunning
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            Redis::connection()->ping();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Queue system unavailable',
            ], 503);
        }

        return $next($request);
    }
}
