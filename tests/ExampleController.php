<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Controller\AbstractController;
use Luminar\Http\Response;
use Luminar\Router\Route;

class ExampleController extends AbstractController
{
    public static string $response = "Hello World";

    #[Route("/example", methods: "GET")]
    public function index(): Response
    {
        return $this->text($this::$response);
    }
}