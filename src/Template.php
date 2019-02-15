<?php

namespace Simple;

/**
 * Very simple and easy to use PHP template engine.
 * Instead of working with fancy custom syntax, this engine relies on pure php and html
 */
class Template
{
    /**
     * @var array All existing sections for current template
     */
    private static $sections = [];

    /**
     * @var array Render content (HTML) for sections in current template
     */
    private static $sectionsContent = [];

    /**
     * @var array Data for each section
     */
    private static $sectionData = [];

    /**
     * @var string Base template used for currently rendering template
     */
    private static $baseTemplate = null;

    /**
     * Prevent direct instantiating
     */
    private function __construct()
    {
    }

    /**
     * Set content of section, callback return value will be used as content
     * 
     * @param string $sectionName Section that will contain provided content
     * @param callable $renderCallback Function whos retrun value will be used as section content
     */
    public static function SectionContent(string $sectionName, callable $renderCallback): void
    {
        if (empty(self::$sections[$sectionName])) {
            self::$sections[$sectionName] = [];
        }

        self::$sections[$sectionName][] = $renderCallback;
    }

    /**
     * Sets data for single section
     * 
     * @param string $sectionName Name of section to which data applies
     * @param array $data Data that will be applied to section
     * @param bool $strict Flag that determines should application fail if section doesn't exists
     */
    public static function SetSectionData(string $sectionName, array $data, bool $strict = false): void
    {
        $section = self::$sections[$sectionName] ?? [];

        if (empty($section) && $strict) {
            throw new \RuntimeException("Invalid section '{$sectionName}'");
        }

        self::$sectionData[$sectionName] = $data;
    }

    /**
     * Print section contents
     * 
     * @param string $sectionName Name of section that will be printed out
     * @param bool $strict Flag that determines should application fail if section doesn't have any contents
     */
    public static function RenderSection(string $sectionName, bool $strict = false): void
    {
        if (false === isset(self::$sectionsContent[$sectionName]) && $strict) {
            throw new \RuntimeException("Invalid section '{$sectionName}'");
        }

        echo self::$sectionsContent[$sectionName] ?? '';
    }

    /**
     * Include partial into current template
     * 
     * @param string $partialPath Path to file that is treated as partial include of an template
     */
    public static function Partial(string $partialPath): void
    {
        if (false === is_readable($partialPath)) {
            throw new \RuntimeException("Unable to read partial '{$partialPath}'");
        }

        require_once $partialPath;
    }

    /**
     * Include multiple partials into current template
     * 
     * @param array $paths Array of paths to files that are treated as partial include of an template
     */
    public static function Partials(array $paths): void
    {
        foreach ($paths as $path) {
            self::Partial($path);
        }
    }

    /**
     * Set current template base
     * 
     * @param string $baseTemplate Path to valid base template
     */
    public static function SetBase(string $baseTemplate): void
    {
        if (false === is_readable($baseTemplate)) {
            throw new \RuntimeException("Cannot read file '{$baseTemplate}'");
        }

        self::$baseTemplate = $baseTemplate;
    }

    /**
     * Render single template into a string
     *
     * @param string $template Template that needs to be rendered
     * @param array $data Data that are passed to template callbacks
     * @param bool $dataPerSection Flag that determines are data global or per section
     * @throws \ReflectionException
     */
    public static function Render(string $template, array $data = [], bool $dataPerSection = false): void
    {
        if (false === is_readable($template)) {
            throw new \RuntimeException("Unable to read template '{$template}'");
        }

        require_once $template;

        if (null === self::$baseTemplate) {
            throw new \RuntimeException('Base Template not defined');
        }

        ob_start();

        foreach (self::$sections as $sectionName => $renderCallbacks) {
            self::SetSectionData($sectionName, $dataPerSection ? ($data[$sectionName] ?? []) : $data);

            self::ExecuteRenderCallbacks($sectionName, $renderCallbacks);
        }

        require_once self::$baseTemplate;

        $html = ob_get_contents();

        ob_end_flush();
    }

    /**
     * Execute all section callbacks with proper arguments passed
     *
     * @param string $sectionName Name of section that is being processed
     * @param array $callbacks Functions that will be invoket to retrieve section content
     * @throws \ReflectionException
     */
    private static function ExecuteRenderCallbacks(string $sectionName, array $callbacks)
    {
        $content = '';

        foreach ($callbacks as $callback) {
            $content .= self::InvokeSectionCallback($sectionName, $callback);
        }
        
        self::$sectionsContent[$sectionName] = $content;
    }

    /**
     * Execute single section callback with proper arguments passed
     *
     * @param string $sectionName Name of section that is being processed
     * @param callable $callback Function that is being invoked
     * @return string
     * @throws \ReflectionException
     */
    private static function InvokeSectionCallback(string $sectionName, callable $callback): string
    {
        $reflection = new \ReflectionFunction($callback);
        $parameters = [];

        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if (isset(self::$sectionData[$sectionName][$parameterName])) {
                $parameters[] = self::$sectionData[$sectionName][$parameterName];

                continue;
            }

            if ($parameter->isOptional()) {
                $parameters[] = $parameter->getDefaultValue() ?? null;

                continue;
            }

            throw new \RuntimeException("Missing parameter '{$parameterName}' for section '{$sectionName}' callback");
        }

        ob_start();

        $reflection->invokeArgs($parameters);

        $html = ob_get_contents();

        ob_clean();

        return $html;
    }
}