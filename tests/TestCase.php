<?php

namespace Rahat1994\SparkcommerceRestRoutes\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Rahat1994\SparkcommerceRestRoutes\SparkcommerceRestRoutesServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Rahat1994\\SparkcommerceRestRoutes\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            SparkcommerceRestRoutesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_sparkcommerce-rest-routes_table.php.stub';
        $migration->up();
        */
    }
}
