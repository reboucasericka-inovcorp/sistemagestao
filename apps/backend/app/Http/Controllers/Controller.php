<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @method void authorizeResource(string $model, ?string $parameter = null, array $options = [], ?string $middleware = null)
 */


class Controller extends BaseController
{
    use AuthorizesRequests;
}
