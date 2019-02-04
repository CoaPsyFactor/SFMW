<?php

namespace Simple;

abstract class Controller
{
    public const STATUS_OK = 200;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHENTICATED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_INTERNAL = 500;

    public const INVALID_STATUS_CODE = -1;
    public const MISSING_PARAMETER_ID = 0;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    private const GLOBAL_ERROR_ID = '_global';

    private static $routes = [
        self::METHOD_GET => [],
        self::METHOD_POST => [],
    ];

    private static $errorHandlers = [
        self::GLOBAL_ERROR_ID => []
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
     * @throws \ReflectionException
     */
    public static function Run(string $identifier): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (false === isset(self::$routes[$method])) {
            throw new \RuntimeException("Request method {$method} not implemented");
        }

        $callback = self::$routes[$method][$identifier] ?? null;

        if (false === is_callable($callback)) {
            self::TriggerErrorHandler(self::STATUS_NOT_FOUND, $identifier);

            return;
        }

        $status = self::InvokeControlCallback($callback);

        if (self::INVALID_STATUS_CODE === $status) {
            throw new \RuntimeException("{$method} handler for {$identifier} must return integer (status code)");
        } else if (self::MISSING_PARAMETER_ID === $status) {
            self::TriggerErrorHandler(self::STATUS_BAD_REQUEST, $identifier);

            return;
        }

        http_response_code($status);
    }

    /**
     * Register error handler for specific action and status code
     *
     * @param int $statusCode
     * @param callable $callback
     * @param string $id
     */
    public static function RegisterErrorHandler(
        int $statusCode, callable $callback, string $id = self::GLOBAL_ERROR_ID
    ): void
    {
        self::$errorHandlers[$id][$statusCode] = $callback;
    }

    /**
     * Execute status code specified error handler for provided action identifier
     *
     * @param int $statusCode HTTP status code that will be set, also used to determine error handler callback
     * @param string $id Name of action whos error handler should be used if available
     */
    public static function TriggerErrorHandler(int $statusCode, string $id = null): void
    {
        http_response_code($statusCode);

        (
            self::$errorHandlers[$id][$statusCode] ??
            (
                self::$errorHandlers[self::GLOBAL_ERROR_ID][$statusCode] ??
                function () use ($statusCode, $id) {
                    throw new \RuntimeException("Unhandled error for status [{$id}]: {$statusCode}");
                }
            )
        )();
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
     * @return int
     * @throws \ReflectionException
     */
    private static function InvokeControlCallback(callable $callback): int
    {
        $inputParameters = filter_input_array(
            $_SERVER['REQUEST_METHOD'] === self::METHOD_GET ? INPUT_GET : INPUT_POST
        );

        $parameters = [];
        $reflection = new \ReflectionFunction($callback);

        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if (isset($inputParameters[$parameterName])) {
                $parameters[] = $inputParameters[$parameterName];

                continue;
            }

            if ($parameter->isOptional()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            return self::MISSING_PARAMETER_ID;
        }

        $status = $reflection->invokeArgs($parameters) ?? self::STATUS_OK;

        return is_int($status) ? $status : self::INVALID_STATUS_CODE;
    }
}