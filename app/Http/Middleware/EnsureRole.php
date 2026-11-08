<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penegakan role: ->middleware('role:admin,eic').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless($user->hasRole(...$roles), 403, 'Anda tidak memiliki akses untuk halaman ini.');

        return $next($request);
    }
}
