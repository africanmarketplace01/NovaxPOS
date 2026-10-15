<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Policies\ModelPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        // ModelPolicy stub — prevents ReflectionException on php artisan runs
        User::class => ModelPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
