<?php

namespace App\Http\Middleware;

use App\Models\App\MaintenanceWindow;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/app/*')) {
            return $next($request);
        }

        $window = MaintenanceWindow::current();
        if (! $window) {
            return $next($request);
        }

        if (is_array($window->allowed_ips) && in_array($request->ip(), $window->allowed_ips, true)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => $window->message_ar ?? $window->message ?? 'التطبيق تحت الصيانة',
            'maintenance' => [
                'starts_at' => $window->starts_at?->toIso8601String(),
                'ends_at' => $window->ends_at?->toIso8601String(),
            ],
        ], 503);
    }
}
