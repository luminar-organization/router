<?php

namespace Luminar\Router\Tests;

use Luminar\Core\Container\Container;
use Luminar\Http\Response;
use Luminar\Router\Router;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class RouterTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * @throws ReflectionException
     */
    public function testRouteRegistration(): void
    {
        $router = new Router($this->container);
        $router->registerRoutes("Luminar\\Router\\Tests\\ExampleController");

        $this->assertArrayHasKey("GET", $router->getRoutes());
        $this->assertArrayHasKey("/example", $router->getRoutes()["GET"]);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testRouteDispatch(): void
    {
        $router = new Router($this->container);
        $router->registerRoutes("Luminar\\Router\\Tests\\ExampleController");

        /**
         * @var Response $response
         */
        $response = $router->dispatch("GET", '/example');
        $this->assertEquals(ExampleController::$response, $response->getResponse());
    }
}