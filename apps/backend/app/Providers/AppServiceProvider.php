<?php

namespace App\Providers;

use App\Models\ContactModel;
use App\Models\EntityModel;
use App\Policies\ContactPolicy;
use App\Policies\EntityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(EntityModel::class, EntityPolicy::class);
        Gate::policy(ContactModel::class, ContactPolicy::class);
    }
}
