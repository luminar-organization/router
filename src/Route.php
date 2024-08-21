<?php

namespace Luminar\Router;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public string $path;
    public array $methods;

    public function __construct(string $path, string $methods = "GET")
    {
        $this->path = $path;
        $this->methods = explode("|", $methods);
    }
}