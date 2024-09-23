<?php

namespace Luminar\Router;

use Luminar\Http\Request;
use Luminar\Http\Response;

interface MiddlewareInterface
{
    public function before(Request $request): void;
    public function after(Request $request): void;
}