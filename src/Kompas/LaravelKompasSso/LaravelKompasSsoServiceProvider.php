<?php 
namespace Kompas\LaravelKompasSso;

use Illuminate\Auth\AuthServiceProvider;


class LaravelKompasSsoServiceProvider extends AuthServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('kompas/laravel-kompas-auth', null, realpath(__DIR__.'/../../'));
        parent::boot();

        $this->app['auth']->extend('ssokompas', function($app) {
            return new KompasSsoGuard(new KompasUserProvider(), $app['session.store']);
        });
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        parent::register();

        $app = $this->app;

        $app['router']->filter('kompas-finish-authentication', function($route, $request) use ($app) {
            return $app['auth']->finishAuthenticationIfRequired();
        });

	}
}
