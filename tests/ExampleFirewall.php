<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Request;
use Luminar\Http\Response;
use Luminar\Router\FirewallInterface;

class ExampleFirewall implements FirewallInterface
{
    public function validate(Request $request): bool
    {
        return 1 !== 1;
    }

    public function onFail(Request $request): Response
    {
        // Your code when validate returns false
        return new Response("Firewall failed", 403);
    }
}