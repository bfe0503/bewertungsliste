<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{pattern:string,handler:mixed,paramNames:string[]}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /** Register a GET route. */
    public function get(string $path, $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** Register a POST route. */
    public function post(string $path, $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** Add internal route with a simple {param} placeholder syntax. */
    private function add(string $method, string $path, $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function ($m) use (&$paramNames) {
                $paramNames[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            rtrim($path, '/')
        );
        if ($pattern === '') {
            $pattern = '/';
        }
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'pattern'    => $pattern,
            'handler'    => $handler,
            'paramNames' => $paramNames,
        ];
    }

    /** Dispatch request; treats HEAD like GET (for curl -I / uptime checks). */
    public function dispatch(string $method, string $path): void
    {
        // Normalize method for HEAD requests.
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $m)) {
                // Collect only named captures into params array.
                $params = [];
                foreach ($m as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }
                $this->invoke($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    /**
     * Resolve and call handler:
     * - callable
     * - "Class@method"
     * - [ClassName, "method"]  (instantiated lazily)
     * - [object, "method"]
     */
    private function invoke($handler, array $params): void
    {
        // "Class@method"
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    $instance->$method($params);
                    return;
                }
            }
        }

        // [ClassName, method] → instantiate
        if (is_array($handler)
            && isset($handler[0], $handler[1])
            && is_string($handler[0])
            && class_exists($handler[0])) {
            $instance = new $handler[0]();
            $method = (string)$handler[1];
            if (method_exists($instance, $method)) {
                $instance->$method($params);
                return;
            }
        }

        // [object, method] or any callable
        if (is_callable($handler)) {
            $handler($params);
            return;
        }

        throw new \RuntimeException('Route handler is not callable/resolveable');
    }
}
