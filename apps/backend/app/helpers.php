<?php

use App\Models\Settings\CompanyModel;
use App\Services\Settings\Company\CompanyService;

if (!function_exists('company')) {
    function company(): CompanyModel
    {
        return app(CompanyService::class)->getCurrent();
    }
}
