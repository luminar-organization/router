<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Controller\AbstractController;
use Luminar\Http\Response;
use Luminar\Router\Route;

class FirewallController extends AbstractController
{
    #[Route("/firewall", methods: "GET", firewall: new ExampleFirewall())]
    public function index(): Response
    {
        return $this->text("Hello World!");
    }
}