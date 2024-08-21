<?php

namespace Luminar\Router;

use Exception;
use Luminar\Core\Container\Container;
use Luminar\Core\Container\DependencyInjection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

class Router
{
    /**
     * @var DependencyInjection $dependencyInjection
     */
    protected DependencyInjection $dependencyInjection;

    /**
     * @var array $routes
     */
    protected array $routes = [];

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function __construct(Container $container)
    {
        $this->dependencyInjection = new DependencyInjection($container);
    }

    /**
     * @param string $controllerClass
     * @return void
     * @throws ReflectionException
     */
    public function registerRoutes(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();
                $this->addRoute($route->methods, $route->path, [$controllerClass, $method->getName()]);
            }
        }
    }

    public function addRoute(array $methods, string $path, $handler): void
    {
        foreach ($methods as $method) {
            $this->routes[$method][$path] = $handler;
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @return mixed
     * @throws ReflectionException
     */
    public function dispatch(string $method, string $path): mixed
    {
        if (isset($this->routes[$method][$path])) {
            return $this->invoke($this->routes[$method][$path]);
        }

        throw new RuntimeException("Route not found.");
    }

    /**
     * @param $handler
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     */
    private function invoke($handler): mixed
    {
        if(is_callable($handler)) {
            return $this->dependencyInjection->build($handler);
        }

        if(is_array($handler)) {
            [$class, $method] = $handler;
            $instance = $this->dependencyInjection->build($class);
            if(!method_exists($instance, $method)) {
                throw new RuntimeException("Method [$method] not found.");
            }

            return call_user_func([$instance, $method], []);
        }

        throw new RuntimeException("Invalid route Handler");
    }
}