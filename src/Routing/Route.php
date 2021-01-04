<?php

namespace Swift\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use Swift\Foundation\App;
use function FastRoute\simpleDispatcher;

/**
 * Class Route
 * @package Swift\Routing
 */
class Route
{
    /**
     * @var Route
     */
    protected static $_instance = null;

    /**
     * @var GroupCountBased
     */
    protected static $_dispatcher = null;

    /**
     * @var RouteCollector
     */
    protected static $_collector = null;

    /**
     * @var bool
     */
    protected static $_hasRoute = false;

    /**
     * @var null|callable
     */
    protected static $_fallback = null;

    /**
     * @var array
     */
    protected static $_nameList = [];

    /**
     * @var callable
     */
    protected static $_groupPrefix = '';

    /**
     * @var BaseRoute[]
     */
    protected $_routes = [];

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function get($path, $callback)
    {
        return static::addRoute('GET', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function post($path, $callback)
    {
        return static::addRoute('POST', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function put($path, $callback)
    {
        return static::addRoute('PUT', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function patch($path, $callback)
    {
        return static::addRoute('PATCH', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function delete($path, $callback)
    {
        return static::addRoute('DELETE', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function head($path, $callback)
    {
        return static::addRoute('HEAD', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function options($path, $callback)
    {
        return static::addRoute('OPTIONS', $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function any($path, $callback)
    {
        return static::addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'], $path, $callback);
    }

    /**
     * @param $method
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    public static function add($method, $path, $callback)
    {
        return static::addRoute($method, $path, $callback);
    }

    /**
     * @param $path
     * @param $callback
     */
    public static function group($path, $callback)
    {
        static::$_groupPrefix = $path;
        $instance = static::$_instance = new static;
        static::$_collector->addGroup($path, $callback);
        static::$_instance = null;
        static::$_groupPrefix = '';
        return $instance;
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        foreach ($this->_routes as $route) {
            $route->middleware($middleware);
        }
    }

    /**
     * @param BaseRoute $route
     */
    public function collect(BaseRoute $route)
    {
        $this->_routes[] = $route;
    }

    /**
     * @param $name
     * @param BaseRoute $instance
     */
    public static function setByName($name, BaseRoute $instance)
    {
        static::$_nameList[$name] = $instance;
    }

    /**
     * @param $name
     * @return null|BaseRoute
     */
    public static function getByName($name)
    {
        return static::$_nameList[$name] ?? null;
    }

    /**
     * @param $method
     * @param $path
     * @return array
     */
    public static function dispatch($method, $path)
    {
        return static::$_dispatcher->dispatch($method, $path);
    }

    /**
     * @param $path
     * @param $callback
     * @return array|bool|callable
     */
    public static function convertToCallable($path, $callback)
    {
        if (is_array($callback)) {
            $callback = array_values($callback);
        }

        if (is_callable($callback)) {
            if (is_array($callback) && is_string($callback[0])) {
               return [App::container()->get($callback[0]), $callback[1]];
            }
            return $callback;
        }

        $callback = explode('@', $callback);
        if (isset($callback[1]) && class_exists($callback[0]) && is_callable([App::container()->get($callback[0]), $callback[1]])) {
            return [App::container()->get($callback[0]), $callback[1]];
        }
        echo "Route set to $path is not callable\n";
        return false;
    }

    /**
     * @param $method
     * @param $path
     * @param $callback
     * @return BaseRoute
     */
    protected static function addRoute($method, $path, $callback)
    {
        static::$_hasRoute = true;
        $route = new BaseRoute($method, static::$_groupPrefix . $path, $callback);
        if ($callback = static::convertToCallable($path, $callback)) {
            static::$_collector->addRoute($method, $path, ['callback' => $callback, 'route' => $route]);
        }
        if (static::$_instance) {
            static::$_instance->collect($route);
        }
        return $route;
    }

    /**
     * @return bool
     */
    public static function load($route_config_file)
    {
        static::$_dispatcher = simpleDispatcher(function (RouteCollector $route) use ($route_config_file) {
            Route::setCollector($route);
            if (is_file($route_config_file)) {
                require_once $route_config_file;
            }
        });
        return static::$_hasRoute;
    }

    /**
     * @param $route
     */
    public static function setCollector($route)
    {
        static::$_collector = $route;
    }

    /**
     * @param callable $callback
     */
    public static function fallback(callable $callback)
    {
        if (is_callable($callback)) {
            static::$_fallback = $callback;
        }
    }

    /**
     * @return callable|null
     */
    public static function getFallback()
    {
        return is_callable(static::$_fallback) ? static::$_fallback : null;
    }
}
