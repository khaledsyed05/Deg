<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $user->hasAnyRole(['club_manager', 'club_admin', 'club_staff'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Club access required.',
            ], 403);
        }

        $clubId = $user->managed_club_id ?? $user->staff_club_id;

        if (! $clubId) {
            return response()->json([
                'success' => false,
                'message' => 'No club assigned to this user.',
            ], 403);
        }

        $request->attributes->set('club_id', (int) $clubId);

        return $next($request);
    }
}
