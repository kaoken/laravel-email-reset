<?php

namespace Kaoken\LaravelMailReset;

use Illuminate\Support\ServiceProvider;

class MailResetServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The basic path of the library here.
     * @param string $path
     * @return string
     */
    protected function my_base_path($path='')
    {
        return __DIR__.'/../'.$path;
    }

    /**
     * The basic path of the library here.
     * @param string $path
     * @return string
     */
    protected function my_resources_path($path='')
    {
        return $this->my_base_path('resources/'.$path);
    }


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->my_resources_path('views') => resource_path('views/vendor/mail_reset'),
                $this->my_resources_path('lang') => resource_path('lang'),
                $this->my_base_path('database/migrations') => database_path('migrations'),
            ], 'mail-reset');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerMailResetBroker();
    }

    /**
     * Register the email reset broker instance.
     *
     * @return void
     */
    protected function registerMailResetBroker()
    {
        $this->app->singleton('auth.mail_reset', function ($app) {
            return new MailResetBrokerManager($app);
        });

        $this->app->bind('auth.mail_reset.broker', function ($app) {
            return $app->make('auth.mail_reset')->broker();
        });
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['auth.mail_reset', 'auth.mail_reset.broker'];
    }
}
