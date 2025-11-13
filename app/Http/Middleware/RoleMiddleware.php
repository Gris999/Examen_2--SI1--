<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:administrador,admin,director')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();
        if (!$user) { abort(401); }

        $have = $user->roles()->pluck('nombre')->map(fn($n)=>mb_strtolower($n))->toArray();
        // Normaliza parámetros
        $allowed = collect($roles)->flatMap(fn($r)=>explode('|', (string)$r))
            ->map(fn($n)=>mb_strtolower(trim($n)))
            ->filter()->values()->all();

        if (empty($allowed)) {
            return $next($request);
        }

        // Permite si al menos uno coincide
        foreach ($have as $h) {
            if (in_array($h, $allowed, true)) {
                return $next($request);
            }
        }

        abort(403);
    }
}

