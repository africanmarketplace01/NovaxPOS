<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\JobPipeline\JobPipeline;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // ── CRITICAL: Bootstrap tenancy when a tenant is initialized ──────────
        // Without these two listeners, DatabaseTenancyBootstrapper::bootstrap()
        // is never called, so the 'tenant' DB connection is never created and
        // tenants:migrate runs on the default (landlord) connection instead.
        Event::listen(Events\TenancyInitialized::class, Listeners\BootstrapTenancy::class);
        Event::listen(Events\TenancyEnded::class,       Listeners\RevertToCentralContext::class);

        // ── Create tenant database when a new tenant is registered ────────────
        // Jobs\CreateDatabase uses MySQLDatabaseManager::createDatabase() to
        // issue CREATE DATABASE for the new tenant automatically on Tenant::create().
        Event::listen(Events\TenantCreated::class,
            JobPipeline::make([Jobs\CreateDatabase::class])
                ->send(fn (Events\TenantCreated $event) => $event->tenant)
                ->shouldBeQueued(false)
                ->toListener()
        );

        // Auto-delete tenant DB when tenant is destroyed (optional — comment out
        // if you want to keep data for recovery purposes).
        // Event::listen(Events\TenantDeleted::class,
        //     JobPipeline::make([Jobs\DeleteDatabase::class])
        //         ->send(fn (Events\TenantDeleted $event) => $event->tenant)
        //         ->shouldBeQueued(false)
        //         ->toListener()
        // );
    }
}
