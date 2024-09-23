<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Request;
use Luminar\Router\MiddlewareInterface;

class ExampleMiddleware implements MiddlewareInterface
{
    public function after(Request $request): void
    {
        // Your code after response is ready
    }

    public function before(Request $request): void
    {
        // Your code before request is sent to Controller
    }
}