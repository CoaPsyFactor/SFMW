<?php

namespace Simple;


class Framework
{
    /** @var string */
    public const DEFAULT_PAGE_IDENTIFIER = 'page';

    /** @var string */
    public const DEFAULT_ROOT_DIRECTORY = '/';

    /** @var callable[] */
    private static $exceptionHandlers = [];

    /** @var array */
    private static $requestTemplates = [];

    /** @var string[] */
    private static $requestRedirects = [];

    /** @var callable[] */
    private static $errorHandlers = [];

    /** @var string */
    private static $pageIdentifier;

    /** @var string */
    private static $rootDirectory = __DIR__;

    /** @var Framework|null */
    private static $initialized = null;

    private function __construct()
    {
    }

    /**
     * Prepare database connection and other framework stuff :)
     *
     * @param array $config
     */
    public static function Initialize(array $config): void
    {
        if (null === self::$initialized) {
            self::$initialized = new self();
        }

        session_start();

        ['framework' => $framework, 'database' => $database] = $config;

        $database && Database::AddConnection($database);
        self::$pageIdentifier = $framework['pageIdentifier'] ?? self::DEFAULT_PAGE_IDENTIFIER;
        self::$rootDirectory = $config['framework']['rootDirectory'] ?? self::DEFAULT_ROOT_DIRECTORY;
    }

    /**
     * Register global framework exception handler
     *
     * @param string $exception Expected exception class
     * @param callable $callback Function that is executed when matched exception is thrown
     */
    public static function Catch(string $exception, callable $callback): void
    {
        self::$exceptionHandlers[$exception] = $callback;
    }

    /**
     * Used to trigger render and output at request ending
     *
     * @throws \ReflectionException
     */
    public function __destruct()
    {
        self::Trigger();
    }

    /**
     * Throw exception that is handled by "Framework::Catch"
     *
     * @param \Exception $exception
     */
    public static function Throw(\Exception $exception): void
    {
        (self::$exceptionHandlers[get_class($exception)] ?? function (\Exception $e) { throw $e;})($exception);
    }

    /**
     * Add handler when request finishes with flag "error=true"
     *
     * @param int $statusCode Status code that will trigger provided callback
     * @param string $viewPath Path to view (template) file
     */
    public static function RegisterErrorPage(int $statusCode, string $viewPath): void
    {
        self::$errorHandlers[$statusCode] = $viewPath;
    }

    /**
     * Register GET request handler, $controller return array represent data that are passed to $template
     * when rendering
     *
     * @param string $page Action (route) that will trigger provided controller
     * @param string $template Template that will be rendered
     * @param string $controller Controller file that provides data to template
     * @throws
     */
    public static function RegisterPage(string $page, string $template, string $controller): void
    {
        $control = self::GetControlFromController($controller);

        self::On($page, $control);
        self::$requestTemplates[$page] = self::$rootDirectory . $template;
    }

    /**
     * Register POST request handler, $controller return array represent data that are used to generate
     * query data and pass to GET request with proper status code
     *
     * @param string $page Page identifier
     * @param string $controller Controller file that contains control function
     * @param string $redirectPage Page where successful post will be redirected
     * @throws
     */
    public static function RegisterControl(string $page, string $controller, string $redirectPage): void
    {
        $control = self::GetControlFromController($controller);

        self::On($page, null, $control);
        self::$requestRedirects[$page] = $redirectPage;
    }

    /**
     * Load and validate controller control callback
     *
     * @param string $controller Controller path
     * @return \Closure
     * @throws \ReflectionException
     */
    private static function GetControlFromController(string $controller): \Closure
    {
        $controller = self::$rootDirectory . $controller;

        if (false === is_readable($controller)) {
            self::Throw(new \RuntimeException("Invalid controller path '{$controller}'"));
        }

        /** @var \Closure $control */
        $control = require $controller;

        if (false === $control instanceof \Closure) {
            self::Throw(new \RuntimeException("Invalid control {$controller}"));
        }

        $returnType = (new \ReflectionFunction($control))->getReturnType();

        if (null === $returnType || strcmp($returnType, 'array')) {
            self::Throw(new \RuntimeException("Control return value must be an array. {$controller}"));
        }

        return $control;
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
            $method === Control::METHOD_GET && $methodGet && Control::Get($action, $methodGet);
            $method === Control::METHOD_POST && $methodPost && Control::Post($action, $methodPost);
        } catch (\Exception $exception) {
            self::Throw($exception);
        }
    }

    /**
     * Trigger registered action if any
     *
     * @throws \ReflectionException
     */
    private static function Trigger(): void
    {
        $page = $_REQUEST[self::$pageIdentifier] ?? '';

        try {
            $result = Control::Run($page) ?? ['status' => StatusCode::INTERNAL, 'message' => 'Something went wrong.'];
        } catch (\Exception $exception) {
            self::Throw($exception);

            return;
        }

        http_response_code(
            is_int($result['status'] ?? false) ? $result['status'] : StatusCode::INTERNAL
        );

        ($result['error'] ?? false) && self::HandleTriggerError($result);

        self::Finish($page, $result);
    }

    /**
     * @param array $result
     * @throws \ReflectionException
     */
    private static function HandleTriggerError(array $result): void
    {
        $viewPath = self::$errorHandlers[$result['status'] ?? StatusCode::INTERNAL] ?? null;

        if (null !== $viewPath && false === is_readable($viewPath)) {
            self::Throw(new \RuntimeException("Invalid path {$viewPath}"));

            return;
        } else if (null === $viewPath) {
            ['status' => $status, 'message' => $message] = $result;

            self::Throw(new \RuntimeException("Request failed with status {$status}. {$message}"));

            return;
        }

        Template::Render($viewPath, $result);
    }

    /**
     * Finish request on proper way based on existing data
     *
     * @param string $page
     * @param array $result
     * @throws \ReflectionException
     */
    private static function Finish(string $page, array $result): void
    {
        (self::$requestTemplates[$page] ?? false) && Template::Render(self::$requestTemplates[$page], $result);

        if (self::$requestRedirects[$page] ?? false) {
            $query = http_build_query([self::$pageIdentifier => self::$requestRedirects[$page]] + $result);
            [$path,] = explode('?', $_SERVER['REQUEST_URI'] ?? '?');
            $url = "{$path}?{$query}";

            header("Location: {$url}");
        }
    }
}