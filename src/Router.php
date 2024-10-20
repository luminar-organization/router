<?php

namespace Luminar\Router;

use Exception;
use Luminar\Core\Container\Container;
use Luminar\Core\Container\DependencyInjection;
use Luminar\Core\Exceptions\DependencyInjectionException;
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
                $this->addRoute($route->methods, $route->path, [$controllerClass, $method->getName()], $route->middleware ?? null, $route->firewall ?? null);
            }
        }
    }

    public function addRoute(array $methods, string $path, $handler, MiddlewareInterface $middleware = null, FirewallInterface $firewall = null): void
    {
        foreach ($methods as $method) {
            $this->routes[$method][$path] = [
                'handler' => $handler,
                'middleware' => $middleware,
                'firewall' => $firewall
            ];
        }
    }

    /**
     * @param string $serverRequestUri
     * @return string
     */
    public function getCurrentUri(string $serverRequestUri): string
    {
        $uri = rawurldecode($serverRequestUri);

        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * @return string
     */
    protected function getBasePath(): string
    {
        return implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @param $matches
     * @param $flags
     * @return bool
     */
    protected function patternMatch(string $pattern, string $uri, &$matches, $flags): bool
    {
        $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

        return boolval(preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE));
    }

    /**
     * @param string $method
     * @param string $path
     * @return mixed
     * @throws ReflectionException|DependencyInjectionException
     */
    public function dispatch(string $method, string $path): mixed
    {
        if(!isset($this->routes[$method])) {
            return $this->invoke($this->routes["GET"]["/404"], []);
        }
        $routes = $this->routes[$method];
        foreach($routes as $routeKey => $routeValue) {
            $is_match = $this->patternMatch($routeKey, $this->getCurrentUri($path), $matches, PREG_OFFSET_CAPTURE);

            if($is_match) {
                $matches = array_slice($matches, 1);

                $params = array_map(function ($match, $index) use ($matches) {

                    if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                        if ($matches[$index + 1][0][1] > -1) {
                            return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                        }
                    }

                    return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
                }, $matches, array_keys($matches));

                return $this->invoke($routeValue, $params);
            }
        }

        if(isset($this->routes["GET"]["/404"])) {
            return $this->invoke($this->routes["GET"]["/404"], []);
        }
        throw new RuntimeException("Route not found.");
    }

    /**
     * @param array $route
     * @param array $params
     * @return mixed
     * @throws ReflectionException
     * @throws DependencyInjectionException
     */
    private function invoke(array $route, array $params): mixed
    {
        $request = new Request($_GET, $this->getRequestBody($_SERVER["REQUEST_METHOD"] ?? "GET"), $this->getRequestHeaders(), $_SERVER["REQUEST_METHOD"] ?? "GET", $_SERVER["REQUEST_URI"] ?? "/", $_SERVER ?? [], $params ?? []);

        $handler = $route['handler'];
        $firewall = $route['firewall'];
        if($firewall) {
            if($firewall instanceof FirewallInterface) {
                if(!$firewall->validate($request)) {
                    return $firewall->onFail($request);
                }
            } else {
                throw new Exception("Firewall is not instance of FirewallInterface!");
            }
        }
        $middleware = $route['middleware'];
        if($middleware and $middleware instanceof MiddlewareInterface) {
            $middleware->before($request);
        }

        if(is_callable($handler)) {
            $response = $this->dependencyInjection->build($handler);
            $middleware->after($request);
            return $response;
        }

        if(is_array($handler)) {
            [$class, $method] = $handler;
            $instance = $this->dependencyInjection->build($class);
            if(!method_exists($instance, $method)) {
                throw new RuntimeException("Method [$method] not found.");
            }

            $response = call_user_func([$instance, $method], $request);
            if($middleware) {
                $middleware->after($request);
            }
            return $response;
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
        }
        if($body !== '') {
            return json_decode($body, true) ?? [];
        }
        return [];
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