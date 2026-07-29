<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = collect($roles)
            ->map(fn (string $role) => RoleEnum::from($role))
            ->contains(fn (RoleEnum $role) => $user?->hasRole($role));

        abort_unless($allowed, 403);

        return $next($request);
    }
}
