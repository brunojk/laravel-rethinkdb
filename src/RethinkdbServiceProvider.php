<?php

namespace brunojk\LaravelRethinkdb;

use brunojk\LaravelRethinkdb\Auth\RethinkUserProvider;
use brunojk\LaravelRethinkdb\Console\Migrations\MigrateMakeCommand;
use brunojk\LaravelRethinkdb\Console\Model\ModelMakeCommand;
use brunojk\LaravelRethinkdb\Eloquent\Model;
use brunojk\LaravelRethinkdb\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;

class RethinkdbServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);

        // Publish config files
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('rethinkdb.php'),
        ]);

        $this->app['auth']->extend('rethink', function($app, $config) {
            return new RethinkUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Merges user's and rethink's configs.
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php', 'rethinkdb'
        );

        $this->app->resolving('db', function ($db) {
            $db->extend('rethinkdb', function ($config) {
                return new Connection($config);
            });
        });


        // Add connector for queue support.
//        $this->app->resolving('queue', function ($queue) {
//            $queue->addConnector('rethinkdb', function () {
//                return new RethinkDBConnector($this->app['db']);
//            });
//        });

        $this->app->singleton('command.rethink-migrate.make', function ($app) {

            $creator = new MigrationCreator($app['files']);
            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });

        $this->commands('command.rethink-migrate.make');

        $this->app->singleton('command.rethink-model.make', function ($app) {
            return new ModelMakeCommand($app['files']);
        });

        $this->commands('command.rethink-model.make');
    }

    public function provides()
    {
        return ['command.rethink-migrate.make', 'command.rethink-model.make'];
    }
}
