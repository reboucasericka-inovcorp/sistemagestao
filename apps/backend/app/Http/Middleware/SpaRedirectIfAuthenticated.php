<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as BaseRedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Igual ao guest do Laravel, mas evita 302 HTML para o SPA quando já há sessão.
 *
 * Caso típico: sessão válida + POST /login → o RedirectIfAuthenticated original
 * faz redirect('/') antes do Fortify; o Axios segue o redirect e rebenta CORS.
 */
final class SpaRedirectIfAuthenticated extends BaseRedirectIfAuthenticated
{
    public function handle($request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($this->prefersJsonResponse($request)) {
                    return response()->json(['message' => 'Already authenticated', 'already_authenticated' => true], 200);
                }

                return redirect($this->redirectTo($request));
            }
        }

        return $next($request);
    }

    private function prefersJsonResponse(Request $request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->expectsJson()
            || $request->acceptsJson();
    }
}
