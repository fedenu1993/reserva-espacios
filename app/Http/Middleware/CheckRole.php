<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {

        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar si el usuario tiene el rol requerido
        $user = Auth::user();
        if ($user->role !== $role) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
