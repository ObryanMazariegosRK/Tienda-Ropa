<?php

namespace App\Http\Middleware;

use App\Domain\Enum\RoleType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{


    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== RoleType::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos de administrador.'
            ], 403);
        }

        return $next($request);
    }
}