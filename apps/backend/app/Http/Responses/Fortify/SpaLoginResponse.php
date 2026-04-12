<?php

namespace App\Http\Responses\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resposta de login compatível com SPA (Axios + Sanctum).
 *
 * O LoginResponse do Fortify só usa wantsJson(); em alguns browsers / negociação
 * de Accept o pedido continua a ser tratado como HTML e devolve 302. O Axios
 * segue o redirect para / ou /home sem CORS → "Network Error".
 */
final class SpaLoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($this->prefersJsonResponse($request)) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended(Fortify::redirects('login'));
    }

    private function prefersJsonResponse($request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->expectsJson()
            || $request->acceptsJson();
    }
}
