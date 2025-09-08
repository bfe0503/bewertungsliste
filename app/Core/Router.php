<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimalistic router supporting GET/POST with {param} placeholders.
 * Accepts handlers as closures/callables OR [ControllerClass::class, 'method'] arrays.
 * PHP 8.2.12
 */
final class Router
{
    /**
     * @var array<string, array<int, array{regex:string, keys:string[], handler:array|callable}>>
     */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    public function get(string $pattern, array|callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array|callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array|callable $handler): void
    {
        [$regex, $keys] = $this->compile($pattern);
        $this->routes[$method][] = ['regex' => $regex, 'keys' => $keys, 'handler' => $handler];
    }

    private function compile(string $pattern): array
    {
        // Convert "/lists/{id}" to regex and capture param names
        $keys = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function ($m) use (&$keys) {
                $keys[] = $m[1];
                return '([^/]+)';
            },
            $pattern
        );
        $regex = '#^' . $regex . '$#';
        return [$regex, $keys];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path   = rtrim($path, '/') ?: '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['keys'] as $idx => $key) {
                    $params[$key] = $matches[$idx] ?? null;
                }

                $handler = $route['handler'];

                // If handler is [ClassName::class, 'method'], instantiate then call
                if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
                    $controller = new $handler[0]();
                    $response = $controller->{$handler[1]}($params);
                } else {
                    // Closure or any other callable
                    $response = ($handler)($params);
                }

                if ($response !== null) {
                    echo (string)$response;
                }
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
