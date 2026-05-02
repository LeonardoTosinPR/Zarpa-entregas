<?php

namespace Core\Router;

use Core\Constants\Constants;
use Core\Exceptions\HTTPException;
use Core\Http\Request;
use Exception;

class Router
{
    private static Router|null $instance = null;
    private array $routes = [];

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new Router();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function addRoute(Route $route): Route
    {
        $this->routes[] = $route;
        return $route;
    }

    public function getRouteSize(): int
    {
        return sizeof($this->routes);
    }

    public function getRoute(int $index): Route
    {
        return $this->routes[$index];
    }

    public function getRoutePathByName(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                $routePath = $route->getUri();
                $routePath = $this->replaceRouteParams($routePath, $params);
                $routePath = $this->appendQueryParams($routePath, $params);
                return $routePath;
            }
        }

        throw new Exception("Route with name $name not found", 500);
    }

    private function replaceRouteParams(string $routePath, &$params): string
    {
        preg_match_all('/\{([a-z_]+)\}/', $routePath, $matches);

        foreach ($matches[1] as $paramName) {
            if (isset($params[$paramName])) {
                $routePath = str_replace('{' . $paramName . '}', $params[$paramName], $routePath);
                unset($params[$paramName]);
            }
        }

        return $routePath;
    }

    private function appendQueryParams(string $routePath, array $params): string
    {
        if (!empty($params)) {
            $routePath .= '?' . http_build_query($params);
        }
        return $routePath;
    }

    public static function init(): void
    {
        $router = self::getInstance();
        require Constants::rootPath()->join('config/routes.php');
        $router->dispatch();
    }

    private function dispatch(): void
    {
        $request = new Request();

        foreach ($this->routes as $route) {
            if ($route->match($request)) {
                $route->runMiddlewares($request);
                $controllerName = $route->getControllerName();
                $actionName = $route->getActionName();
                $controller = new $controllerName();
                $controller->$actionName($request);
                return;
            }
        }

        throw new HTTPException('Page not found', 404);
    }
}
