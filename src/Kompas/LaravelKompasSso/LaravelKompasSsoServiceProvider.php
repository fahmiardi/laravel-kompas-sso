<?php 
namespace Kompas\LaravelKompasSso;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Support\Facades\Redis;
use Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler;

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

        $app['accounts-session'] = $app->share(function () {

            // register handler redis
            ini_set('session.gc_maxlifetime', 25200); // 1 week
            ini_set('session.cookie_httponly', true); // enable httponly
            ini_set('session.cookie_domain', '.kompasiana.com'); // enable all sub domain

            $redis = Redis::connection('accounts-session');
            $handler = new RedisSessionHandler($redis, array(), 'accounts_session');
            session_set_save_handler(
                array($handler, 'open'),
                array($handler, 'close'),
                array($handler, 'read'),
                array($handler, 'write'),
                array($handler, 'destroy'),
                array($handler, 'gc')
            );
            register_shutdown_function('session_write_close');

        });
    }
}
