<?php
/**
 * TCT-YMS Router
 *
 * Handles HTTP routing with support for parameters, middleware, and groups.
 */

namespace App;

class Router
{
    /**
     * Registered routes
     */
    private static array $routes = [];

    /**
     * Named routes
     */
    private static array $namedRoutes = [];

    /**
     * Current group attributes
     */
    private static array $groupStack = [];

    /**
     * Route parameters
     */
    private static array $params = [];

    /**
     * Register GET route
     */
    public static function get(string $path, array|string|callable $handler): Route
    {
        return self::addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public static function post(string $path, array|string|callable $handler): Route
    {
        return self::addRoute('POST', $path, $handler);
    }

    /**
     * Register PUT route
     */
    public static function put(string $path, array|string|callable $handler): Route
    {
        return self::addRoute('PUT', $path, $handler);
    }

    /**
     * Register PATCH route
     */
    public static function patch(string $path, array|string|callable $handler): Route
    {
        return self::addRoute('PATCH', $path, $handler);
    }

    /**
     * Register DELETE route
     */
    public static function delete(string $path, array|string|callable $handler): Route
    {
        return self::addRoute('DELETE', $path, $handler);
    }

    /**
     * Register route for multiple methods
     */
    public static function match(array $methods, string $path, array|string|callable $handler): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = self::addRoute(strtoupper($method), $path, $handler);
        }
        return $route;
    }

    /**
     * Register route for all methods
     */
    public static function any(string $path, array|string|callable $handler): Route
    {
        return self::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $handler);
    }

    /**
     * Create route group with shared attributes
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    /**
     * Add middleware group
     */
    public static function middleware(string|array $middleware): GroupBuilder
    {
        return new GroupBuilder(['middleware' => (array)$middleware]);
    }

    /**
     * Add prefix group
     */
    public static function prefix(string $prefix): GroupBuilder
    {
        return new GroupBuilder(['prefix' => $prefix]);
    }

    /**
     * Add route to registry
     */
    private static function addRoute(string $method, string $path, array|string|callable $handler): Route
    {
        $attributes = self::mergeGroupAttributes();

        // Apply prefix
        if (isset($attributes['prefix'])) {
            $path = '/' . trim($attributes['prefix'], '/') . '/' . ltrim($path, '/');
        }

        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Create route
        $route = new Route($method, $path, $handler);

        // Apply middleware
        if (isset($attributes['middleware'])) {
            $route->middleware($attributes['middleware']);
        }

        self::$routes[$method][$path] = $route;

        return $route;
    }

    /**
     * Merge group attributes
     */
    private static function mergeGroupAttributes(): array
    {
        $merged = [];

        foreach (self::$groupStack as $attributes) {
            if (isset($attributes['prefix'])) {
                $merged['prefix'] = ($merged['prefix'] ?? '') . '/' . trim($attributes['prefix'], '/');
            }
            if (isset($attributes['middleware'])) {
                $merged['middleware'] = array_merge(
                    $merged['middleware'] ?? [],
                    (array)$attributes['middleware']
                );
            }
        }

        return $merged;
    }

    /**
     * Name a route
     */
    public static function nameRoute(string $name, Route $route): void
    {
        self::$namedRoutes[$name] = $route;
    }

    /**
     * Get URL for named route
     */
    public static function route(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \Exception("Route not found: {$name}");
        }

        $route = self::$namedRoutes[$name];
        $path = $route->getPath();

        // Replace parameters
        foreach ($params as $key => $value) {
            $path = preg_replace('/\{' . $key . '\??\}/', $value, $path);
        }

        // Remove optional parameters
        $path = preg_replace('/\{[^}]+\?\}/', '', $path);
        $path = preg_replace('/\/+/', '/', $path);
        $path = rtrim($path, '/');

        return url($path);
    }

    /**
     * Get current route parameters
     */
    public static function getParams(): array
    {
        return self::$params;
    }

    /**
     * Get single route parameter
     */
    public static function param(string $key, mixed $default = null): mixed
    {
        return self::$params[$key] ?? $default;
    }

    /**
     * Dispatch request to matched route
     */
    public static function dispatch(): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Handle PUT/PATCH/DELETE via POST _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Find matching route
        $route = self::findRoute($method, $path);

        if (!$route) {
            abort(404);
        }

        // Run middleware
        $middleware = $route->getMiddleware();
        foreach ($middleware as $mw) {
            self::runMiddleware($mw);
        }

        // Execute handler
        return self::executeHandler($route->getHandler());
    }

    /**
     * Find route matching method and path
     */
    private static function findRoute(string $method, string $path): ?Route
    {
        // Check exact match first
        if (isset(self::$routes[$method][$path])) {
            return self::$routes[$method][$path];
        }

        // Check parameterized routes
        if (!isset(self::$routes[$method])) {
            return null;
        }

        foreach (self::$routes[$method] as $routePath => $route) {
            if ($params = self::matchPath($routePath, $path)) {
                self::$params = $params;
                return $route;
            }
        }

        return null;
    }

    /**
     * Match path with parameters
     */
    private static function matchPath(string $routePath, string $actualPath): ?array
    {
        // Convert route params to regex
        $pattern = preg_replace('/\{([^}]+)\?\}/', '(?<$1>[^/]*)?', $routePath);
        $pattern = preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $actualPath, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key) && $value !== '') {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    /**
     * Run middleware
     */
    private static function runMiddleware(string $middleware): void
    {
        $parts = explode(':', $middleware);
        $name = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $class = "App\\Middleware\\" . ucfirst($name) . 'Middleware';

        if (!class_exists($class)) {
            throw new \Exception("Middleware not found: {$name}");
        }

        $instance = new $class();
        $instance->handle(...$params);
    }

    /**
     * Execute route handler
     */
    private static function executeHandler(array|string|callable $handler): mixed
    {
        // Closure
        if (is_callable($handler)) {
            return call_user_func_array($handler, self::$params);
        }

        // Array [Controller, method]
        if (is_array($handler)) {
            [$class, $method] = $handler;
        }
        // String Controller@method
        elseif (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
        }

        // Prepend namespace if needed
        if (!str_contains($class, '\\')) {
            $class = "App\\Controllers\\{$class}";
        }

        if (!class_exists($class)) {
            throw new \Exception("Controller not found: {$class}");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \Exception("Method not found: {$class}@{$method}");
        }

        return call_user_func_array([$controller, $method], self::$params);
    }

    /**
     * Get all registered routes (for debugging)
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}

/**
 * Route class
 */
class Route
{
    private string $method;
    private string $path;
    private mixed $handler;
    private array $middleware = [];
    private ?string $name = null;

    public function __construct(string $method, string $path, mixed $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array)$middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        Router::nameRoute($name, $this);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}

/**
 * Group builder for fluent route groups
 */
class GroupBuilder
{
    private array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function middleware(string|array $middleware): self
    {
        $this->attributes['middleware'] = array_merge(
            $this->attributes['middleware'] ?? [],
            (array)$middleware
        );
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function group(callable $callback): void
    {
        Router::group($this->attributes, $callback);
    }
}
