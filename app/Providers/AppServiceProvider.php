<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->initForPHPSandboxEnv();
    }

    private function initForPHPSandboxEnv(): void
    {
        $this->app->booted(function (): void {
            $this->configureSecureUrls();
        });
    }

    /**
     * Configure secure URLs
     *
     * This method is used to enforce secure URLs for the application.
     * HTTPS is required for the iframes to work properly and this is the
     * only (accessible) way to enforce that all generated URLs are HTTPS
     * within a PHPSandbox environment.
     *
     * @see https://laravel-news.com/url-force-https
     * @return void
     */
    protected function configureSecureUrls(): void
    {
        // Determine if HTTPS should be enforced
        $enforceHttps = ($this->app->environment(['production', 'staging']) || env('PHPSANDBOX_ENV'))
            && ! $this->app->runningUnitTests();

        // Force HTTPS for all generated URLs
        URL::forceHttps($enforceHttps);

        // Ensure proper server variable is set
        if ($enforceHttps) {
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }
}
