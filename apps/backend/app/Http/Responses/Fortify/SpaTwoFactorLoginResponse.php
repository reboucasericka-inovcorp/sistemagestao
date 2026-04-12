<?php

namespace App\Http\Responses\Fortify;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

final class SpaTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($this->prefersJsonResponse($request)) {
            return new JsonResponse(['two_factor' => true], 200);
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
