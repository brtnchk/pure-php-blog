<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{pattern: string, handler: callable|array{class-string, string}}> */
    private array $routes = [];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->routes[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim(is_string($path) ? $path : '/', '/');

        foreach ($this->routes as $route) {
            $regex = $this->compile($route['pattern']);
            if (preg_match($regex, $path, $m)) {
                $params = array_filter($m, static fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

                return $this->call($route['handler'], $params);
            }
        }

        http_response_code(404);

        return $this->call(['App\\Controllers\\ErrorController', 'notFound'], []);
    }

    private function compile(string $pattern): string
    {
        $regex = (string) preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#';
    }

    /**
     * @param callable|array{class-string, string} $handler
     * @param array<string, mixed> $params
     */
    private function call(callable|array $handler, array $params): mixed
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = $this->container->get($class);
            return $instance->{$method}(...array_values($params));
        }

        return $handler(...array_values($params));
    }
}
