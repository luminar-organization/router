<?php

namespace Luminar\Router;

use Luminar\Http\Request;
use Luminar\Http\Response;

interface FirewallInterface
{
    public function validate(Request $request): bool;
    public function onFail(Request $request): Response;
}