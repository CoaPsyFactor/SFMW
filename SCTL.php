<?php

class SCTL
{
    public const STATUS_OK = 200;
    public const STATUS_BADREQUEST = 400;
    public const STATUS_UNAUTHENTICATED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOTFOUND = 404;
    public const STATUS_INTERNAL = 500;

    private const METHOD_GET = 'GET';
    private const METHOD_POST = 'POST';

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
     */
    public static function Run(string $identifier): void
    {
        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (false === isset(self::$routes[$method])) {

            throw new RuntimeException("Request method {$method} not implemented");
        }

        $callback = self::$routes[$method][$identifier] ?? null;

        if (false === is_callable($callback)) {

            self::TriggerErrorHandler(self::STATUS_NOTFOUND, $identifier);

            return;
        }

        if (false === self::InvokeControlCallback($callback)) {

            self::TriggerErrorHandler(self::STATUS_BADREQUEST, $identifier);
        }
    }

    /**
     * Register error handler for specific action and status code
     */
    public static function RegisterErrorHandler(int $statusCode, callable $callback, string $identifier = self::GLOBAL_ERROR_ID): void
    {
        self::$errorHandlers[$identifier][$statusCode] = $callback;
    }

    /**
     * Execute statuscode specified error handler for provided action identifier
     * 
     * @param int $statusCode HTTP status code that will be set, also used to determine error handler callback
     * @param string $identifier Name of action whos error handler should be used if available
     */
    public static function TriggerErrorHandler(int $statusCode, string $identifier = null): void
    {

        $handler = null;

        if ($identifier && (self::$errorHandlers[$identifier][$statusCode] ?? false)) {

            $handler = self::$errorHandlers[$identifier][$statusCode];
        } else if (self::$errorHandlers[self::GLOBAL_ERROR_ID][$statusCode] ?? false) {

            $handler = self::$errorHandlers[self::GLOBAL_ERROR_ID][$statusCode];
        }

        http_response_code($statusCode);

        $throwUnhandledException = function (int $statusCode) { 
            throw new RuntimeException("Unhandled error for status {$statusCode}");
        };

        (is_callable($handler) && $handler()) || $throwUnhandledException($statusCode);
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

        switch ($method)
        {
            case self::METHOD_GET:
            case self::METHOD_POST:

                if (is_callable(self::$routes[$method][$identifier] ?? null)) {

                    throw new RuntimeException("Route control already registered for {$method} {$identifier}");
                }

                self::$routes[$method][$identifier] = $callback;

                return;
            default:
                throw new RuntimeException("Invalid request method '{$method}'");
        }
    }

    /**
     * Execute handle callback for current request action (route)
     * 
     * @param callable $callback Action handle that needs to be executed
     * 
     * @return bool
     */
    private static function InvokeControlCallback(callable $callback): bool
    {

        $inputParameters = filter_input_array(
            $_SERVER['REQUEST_METHOD'] === self::METHOD_GET ? INPUT_GET : INPUT_POST
        );

        $parameters = [];

        $reflection = new ReflectionFunction($callback);

        foreach ($reflection->getParameters() as $parameter) {
        
            $parameterName = $parameter->getName();

            if (isset($inputParameters[$parameterName])) {

                $parameters[] = $inputParameters[$parameterName];

                continue;
            }

            if ($parameter->isOptional()) {

                $parameters[] = $parameter->getDefaultValue() ?? null;

                continue;
            }

            return false;
        }

        $reflection->invokeArgs($parameters);

        return true;
    }
}