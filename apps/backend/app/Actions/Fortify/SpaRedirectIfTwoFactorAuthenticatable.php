<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;

/**
 * O Fortify original só usa wantsJson() no desafio 2FA → 302 HTML no SPA.
 */
class SpaRedirectIfTwoFactorAuthenticatable extends FortifyRedirectIfTwoFactorAuthenticatable
{
    protected function twoFactorChallengeResponse($request, $user)
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);

        if ($request->ajax()
            || $request->wantsJson()
            || $request->expectsJson()
            || $request->acceptsJson()) {
            return response()->json(['two_factor' => true]);
        }

        return redirect()->route('two-factor.login');
    }
}
