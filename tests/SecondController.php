<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Controller\AbstractController;
use Luminar\Http\Response;
use Luminar\Router\Route;

class SecondController extends AbstractController
{
    #[Route("/second", methods: "GET", middleware: new ExampleMiddleware())]
    public function index(): Response
    {
        return $this->text("Hello World!");
    }
}