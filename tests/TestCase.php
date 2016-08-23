<?php

class TestCase extends Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'brunojk\LaravelRethinkdb\RethinkdbServiceProvider',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // load custom config
        $config = require 'config/database.php';

        // set rethinkdb as default connection
        $app['config']->set('database.default', 'rethinkdb');

        // overwrite database configuration
        $app['config']->set('database.connections.rethinkdb',
            $config['connections']['rethinkdb']);

        // overwrite cache configuration
        $app['config']->set('cache.driver', 'array');

        // try to create new database
        $database = $config['connections']['rethinkdb']['database'];
        $connection = DB::connection()->getConnection();
        try {
            r\dbCreate($database)->run($connection);
        } catch (\Exception $e) {
        }

        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.database', [
            'driver' => 'rethinkdb',
            'table'  => 'jobs',
            'queue'  => 'default',
            'expire' => 60,
        ]);
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs');
        }
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs');
        }

        // FIXME: There should be better way of doing this.
        if (!Schema::hasTable('items')) {
            Schema::create('items', function ($table) {
                $table->index('name')->index('type');
            });
        }
        if (!Schema::hasTable('users')) {
            Schema::create('users');
        }
        if (!Schema::hasTable('books')) {
            Schema::create('books');
        }
        if (!Schema::hasTable('roles')) {
            Schema::create('roles');
        }
        if (!Schema::hasTable('rolers')) {
            Schema::create('rolers');
        }
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions');
        }
        if (!Schema::hasTable('clients')) {
            Schema::create('clients');
        }
        if (!Schema::hasTable('addresses')) {
            Schema::create('addresses');
        }
    }
}
