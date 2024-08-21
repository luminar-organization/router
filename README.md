# Luminar Router
![Tests Status](https://img.shields.io/github/actions/workflow/status/luminar-organization/router/tests.yml?label=Tests)

The Luminar Router is a lightweight routing component for the Luminar PHP framework. It allows you to define and manage routes in your application, supporting dependency injection and advanced route handling.

## Installation

Install via Composer:

```bash
composer require luminar-organization/router
```

# Basic Usage

## Defining Routes

You can define routes using the Route annotation, specifying the HTTP method, the route pattern.

```php

namespace App\Controllers;

use Luminar\Http\Controller\AbstractController;
use Luminar\Http\Response;

class ExampleController extends AbstractController
{
    public static string $response = "Hello World";

    #[Route("/example", methods: "GET")]
    public function index(): Response
    {
        return $this->text($this::$response);
    }
}
```

## Handling Requests
To handle incoming HTTP requests, use the `dispatch` method
```php
$response = $router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);
```

# License
This package is open-sourced software licensed under the [MIT License](LICENSE)

---

This README provides an overview of how to use the `luminar-organization/router` component in your projects. It covers installation, basic routing.