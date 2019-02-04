<?php

namespace Simple;


abstract class Framework
{
    /** @var callable[] */
    private static $exceptionHandlers = [];

    /**
     *
     *
     * @param array $config
     */
    public static function Initialize(array $config): void
    {
        if (is_array($config['database'] ?? false)) {
            Database::addConnection($config['database']);
        }
    }

    /**
     * @param string $exception Expected exception class
     * @param callable $callback Function that is executed when matched exception is thrown
     */
    public static function Catch(string $exception, callable $callback): void
    {
        self::$exceptionHandlers[$exception] = $callback;
    }

    /**
     * Throw exception that is handled by "Framework::Catch"
     *
     * @param \Exception $exception
     */
    public static function Throw(\Exception $exception): void
    {
        (
            self::$exceptionHandlers[get_class($exception)] ??
            function (\Exception $exception) {
                throw $exception;
            }
        )($exception);
    }

    /**
     * Register get and post actions (routes)
     *
     * @param string $action Action (route) that will trigger provided callable
     * @param callable|null $methodGet
     * @param callable|null $methodPost
     */
    public static function On(string $action, ?callable $methodGet = null, ?callable $methodPost = null): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            $method === Controller::METHOD_GET && $methodGet && Controller::Get($action, $methodGet);
            $method === Controller::METHOD_POST && $methodPost && Controller::Post($action, $methodPost);
        } catch (\Exception $exception) {
            self::Throw($exception);
        }
    }

    /**
     * Trigger registered action if any
     *
     * @param string $action Action (route) that will be executed
     * @throws \RuntimeException
     */
    public static function Trigger(string $action): void
    {
        try {
            Controller::Run($action);
        } catch (\Exception $exception) {
            self::Throw($exception);
        }
    }
}