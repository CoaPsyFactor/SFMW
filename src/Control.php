<?php

namespace Simple;

abstract class Control
{


    public const MISSING_PARAMETER_ID = 0;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    private static $routes = [
        self::METHOD_GET => [],
        self::METHOD_POST => [],
    ];

    /**
     * Register GET handler
     *
     * @param string $identifier Name of action
     * @param callable $callback Handle of current action
     */
    public static function Get(string $identifier, callable $callback): void
    {
        self::Route(self::METHOD_GET, $identifier, $callback);
    }

    /**
     * Register POST handler
     *
     * @param string $identifier Name of action
     * @param callable $callback Handle of current action
     */
    public static function Post(string $identifier, callable $callback): void
    {
        self::Route(self::METHOD_POST, $identifier, $callback);
    }

    /**
     * Execute action registered under provided identifier and current server request method
     *
     * @param string $identifier Name of action that should be executed
     * @return mixed
     * @throws \ReflectionException
     */
    public static function Run(string $identifier)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (false === isset(self::$routes[$method])) {
            throw new \RuntimeException("Request method {$method} not implemented");
        }

        $callback = self::$routes[$method][$identifier] ?? null;

        if (false === is_callable($callback)) {
            return ['status' => StatusCode::NOT_FOUND, 'error' => true, 'message' => 'Page not found.'];
        }

        $result = self::InvokeControlCallback($callback);

        if (self::MISSING_PARAMETER_ID === $result) {
            return ['status' => StatusCode::BAD_REQUEST, 'error' => true, 'message' => 'Invalid request parameters.'];
        }

        return $result + ['status' => StatusCode::OK];
    }

    /**
     * Register route and its handler
     *
     * @param string $method
     * @param string $identifier
     * @param callable $callback
     */
    private static function Route(string $method, string $identifier, callable $callback): void
    {
        switch ($method) {
            case self::METHOD_GET:
            case self::METHOD_POST:
                if (is_callable(self::$routes[$method][$identifier] ?? null)) {
                    throw new \RuntimeException("Route control already registered for {$method} {$identifier}");
                }

                self::$routes[$method][$identifier] = $callback;

                return;
            default:
                throw new \RuntimeException("Invalid request method '{$method}'");
        }
    }

    /**
     * Execute handle callback for current request action (route)
     *
     * @param callable $callback Action handle that needs to be executed
     * @return mixed
     * @throws \ReflectionException
     */
    private static function InvokeControlCallback(callable $callback)
    {
        $parameters = [];
        $reflection = new \ReflectionFunction($callback);

        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if (isset($_REQUEST[$parameterName])) {
                $parameters[] = $_REQUEST[$parameterName];

                continue;
            }

            if ($parameter->isOptional()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            return self::MISSING_PARAMETER_ID;
        }

        return $reflection->invokeArgs($parameters);
    }
}