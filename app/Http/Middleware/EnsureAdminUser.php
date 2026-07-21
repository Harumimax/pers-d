<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && mb_strtolower((string) $user->email) === 'harumimax@gmail.com',
            Response::HTTP_FORBIDDEN
        );

        return $next($request);
    }
}
