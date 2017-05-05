<?php

namespace ArtisanSdk\Server\Tests;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use PHPUnit_Framework_TestCase as PHPUnit;

class TestCase extends PHPUnit
{
    protected $app;
    protected $config;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        $this->createApplication();
    }

    /**
     * Create application dependencies required for testing.
     */
    public function createApplication()
    {
        $this->app = Container::getInstance();
        $this->app->singleton('config', function () {
            $server = require_once __DIR__.'/../config/server.php';

            return new Config(compact('server'));
        });
        $this->config = $this->app->make('config');
    }
}
