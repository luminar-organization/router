<?php

namespace Luminar\Router\Tests;

use Luminar\Http\Controller\AbstractController;
use Luminar\Http\Request;
use Luminar\Http\Response;
use Luminar\Router\Route;

class RegexController extends AbstractController
{
    #[Route("/regex/(.*)", methods: "GET")]
    public function index(Request $request): Response
    {
        return $this->text($request->getRequestParam(0));
    }
}