<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Contoh pemakaian di route: ->middleware('role:pemilik')
     * atau untuk beberapa peran: ->middleware('role:kasir,pemilik')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Akun yang dinonaktifkan pemilik langsung dikeluarkan
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun ini sudah dinonaktifkan oleh pemilik.']);
        }

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}