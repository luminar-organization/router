<?php

namespace Luminar\Router;

use Exception;
use Luminar\Core\Container\Container;
use Luminar\Core\Container\DependencyInjection;
use Luminar\Http\Request;
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

            $request = new Request($_GET, $this->getRequestBody($_SERVER["REQUEST_METHOD"]), $this->getRequestHeaders(), $_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], $_SERVER);

            return call_user_func([$instance, $method], $request);
        }

        throw new RuntimeException("Invalid route Handler");
    }

    /**
     * @return array
     */
    protected function getRequestHeaders(): array
    {
        if(function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach($_SERVER as $key => $value) {
            if(str_starts_with($key, "HTTP_")) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }
    protected function getRequestBody(string $method)
    {
        $body = file_get_contents("php://input");
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        switch(true) {
            case str_contains($contentType, 'application/json'):
                return json_decode($body, true);
            case str_contains($contentType, 'application/x-www-form-urlencoded'):
                parse_str($body, $parsedBody);
                return $parsedBody;
            case str_contains($contentType, 'multipart/form-data'):
                if($method !== 'POST') {
                    return $this->parseMultipartFormData($body);
                }
                return $_POST;
            default:
                return $body;
        }
    }

    protected function parseMultipartFormData(string $body): array {
        $parsedData = [];
        $boundary = substr($body, 0, strpos($body, "\r\n"));
        $parts = array_slice(explode($boundary, $body), 1);

        foreach ($parts as $part) {
            if ($part == "--\r\n") break;

            $part = ltrim($part, "\r\n");
            [$headers, $value] = explode("\r\n\r\n", $part, 2);

            $name = '';
            if (preg_match('/name=\"([^\"]*)\"/', $headers, $matches)) {
                $name = $matches[1];
            }

            $value = substr($value, 0, strlen($value) - 2);
            $parsedData[$name] = $value;
        }

        return $parsedData;
    }
}