<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Map the user's role and input roles to uppercase for safe comparison
        if (!in_array(strtoupper($user->role), array_map('strtoupper', $roles))) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
