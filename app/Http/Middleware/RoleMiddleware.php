<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Periksa apakah user memiliki salah satu role yang diizinkan.
     *
     * Penggunaan di route:
     * - single: middleware('role:owner')
     * - multiple: middleware('role:owner,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        // Normalisasi role ke lower-case
        $userRole = strtolower((string)($user->role ?? ''));
        $allowed = array_map(fn ($r) => strtolower(trim($r)), $roles);

        if (empty($allowed)) {
            // Jika tidak diberikan parameter role, tolak akses demi keamanan
            abort(Response::HTTP_FORBIDDEN, 'Role not specified.');
        }

        if (!in_array($userRole, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
