<?php

namespace App\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClubManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('club.login');
        }

        if (! Auth::user()->hasRole('club_manager')) {
            abort(403, 'غير مخوّل للوصول إلى لوحة النادي.');
        }

        return $next($request);
    }
}
