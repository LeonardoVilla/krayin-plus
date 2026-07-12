<?php

namespace App\Providers;

use App\Support\AuditLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\Provider as AzureProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Webkul\Core\ViewRenderEventManager;

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
        Event::listen(SocialiteWasCalled::class, fn (SocialiteWasCalled $event) => $event->extendSocialite('azure', AzureProvider::class));

        Event::listen('admin.sessions.login.form_controls.after', function (ViewRenderEventManager $manager) {
            $manager->addTemplate('entra-id-login-button');
        });

        Event::listen('eloquent.created: *', function ($eventName, array $data) {
            AuditLogger::handle('insert', $data[0]);
        });

        Event::listen('eloquent.updated: *', function ($eventName, array $data) {
            AuditLogger::handle('update', $data[0]);
        });

        Event::listen('eloquent.deleted: *', function ($eventName, array $data) {
            AuditLogger::handle('delete', $data[0]);
        });
    }
}
