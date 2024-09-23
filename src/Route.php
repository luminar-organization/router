<?php

namespace Luminar\Router;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public string $path;
    public array $methods;
    public MiddlewareInterface $middleware;
    public FirewallInterface $firewall;

    public function __construct(string $path, string $methods = "GET", MiddlewareInterface $middleware = null, FirewallInterface $firewall = null)
    {
        if($middleware) {
            $this->middleware = $middleware;
        }
        if($firewall) {
            $this->firewall = $firewall;
        }
        $this->path = $path;
        $this->methods = explode("|", $methods);
    }
}