<?php
namespace Rain;
use Rain\Tpl\NotFoundException;

/**
 * RainTPL4 engine main class
 *
 * @package Rain\TemplateEngine
 * @version 4.0
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class RainTPL4
{
    use Tpl\RainTPLConfiguration;
    use Tpl\RainTPLEventsHandler;

    /**
     * List of all assigned variables
     *
     * @var array
     */
    public $variables = array(

    );

    /**
     * Configuration as associative array
     *
     * @var array
     */
    public $config = array(
        // backwards compatibility
        'raintpl3_plugins_compatibility' => false,
        'print_parsed_code' => false,

        'checksum' => array(),
        'charset' => 'UTF-8',
        'debug' => false,
        'include_path' => array(), // RainTPL4
        'pluginsIncludePath' => array(), // RainTPL4
        'pluginsEnabled' => array(), // RainTPL4
        'tpl_dir' => 'templates/', // RainTPL4
        'cache_dir' => '/tmp/', // RainTPL4
        'tpl_ext' => 'html', // RainTPL4
        //'ignore_single_quote' => true,
        'predetect' => true, // RainTPL4
        'base_url' => '',
        'php_enabled' => false,
        'auto_escape' => true,
        'force_compile' => false, // RainTPL4
        'allow_compile' => true, // RainTPL4
        'allow_compile_once' => true, // allow compile template only once, RainTPL4
        'remove_comments' => false, // RainTPL4
        'registered_tags' => array(),
        'ignore_unknown_tags' => false, // RainTPL4
    );

    /**
     * Registered plugins for RainTPL3 and RainTPL4
     *
     * @var array
     */
    public $plugins = array(

    );

    /**
     * Externally registered tags eg. by plugins
     *
     * @var array
     */
    public $registeredTags = array(

    );

    /**
     * Here you could enable/disable tags from Parser or add your own
     *
     * @var array
     */
    public $tags = array();

    /**
     * List of attached functions for parsing blocks
     *
     * Example: 'myTag' => array($object, 'methodName')
     *
     * @var array
     */
    public $blockParserCallbacks = array(

    );

    /**
     * List of modifier functions
     *
     * @var array
     */
    public $modifiers = array(

    );

    /**
     * Debug backtraces array in debugging mode
     *
     * @var array
     */
    public $debugData = array(
        'assigns' => array(),
        'draws' => array(),
    );

    /**
     * Constructor
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function __construct()
    {
        $this->config['pluginsIncludePath'][] = __DIR__. '/Plugins/';

        // @debug Damian Kęska
        //$this->config['pluginsEnabled'][] = 'pseudoSandboxing';

        /**
         * str_replace modifier
         *
         * @param string $string String to operate on
         * @param string $from Input to replace
         * @param string $to Replacement
         *
         * @author Damian Kęska <damian@pantheraframework.org>
         * @return string
         */
        $this->modifiers['replace'] = function ($string, $from, $to) {
            return str_replace($from, $to, $string);
        };

        /**
         * Cuts of a string to a selected length limit appending ending string, eg. "..."
         *
         * @param $inputString Input string to cut off
         * @param int $limit Char limit
         * @param string $ending Ending string
         *
         * @author Damian Kęska <damian@pantheraframework.org>
         * @return string
         */
        $this->modifiers['cut'] = function ($inputString, $limit = 32, $ending = '...') {
            if (strlen($inputString) <= $limit)
                return $inputString;

            return substr($inputString, 0, ($limit - strlen($ending))) . $ending;
        };

        /**
         * Python-like array/string search
         *
         * Python: "test" in "this is a test"
         * RainTPL modifier: "test"|in:"this is a a test"
         *
         * @param mixed $needle
         * @param array|string $haystack
         *
         * @author Damian Kęska <damian@pantheraframework.org>
         * @return bool
         */
        $this->modifiers['in'] = function ($needle, $haystack) {
            if (is_string($haystack) && is_string($needle))
            {
                return (strpos($haystack, $needle) !== false);
            } elseif (is_array($haystack)) {
                return (in_array($needle, $haystack) || array_key_exists($needle, $haystack));
            }

            return false;
        };
    }

    /**
     * Draw the template
     *
     * @param string $templateFilePath name of the template file
     * @param bool $toString if the method should return a string
     * @param bool $isString if input is a string, not a file path or echo the output
     *
     * @event engine.draw.before $templateFilePath, $toString, $isString
     * @event engine.draw.after $templateFilePath, $toString, $isString, $html
     * @return void|string depending of the $toString
     */
    public function draw($templateFilePath, $toString = FALSE, $isString = FALSE)
    {
        if ($this->getConfigurationKey('debug'))
        {
            $this->debugData['draws'][] = array(
                'templateFilePath' => $templateFilePath,
                'toString' => $toString,
                'isString' => $isString,
                'stacktrace' => debug_backtrace(),
            );
        }

        // re-load plugins
        $this->loadEventHandlers();

        list($templateFilePath, $toString, $isString) = $this->executeEvent('engine.draw.before', array($templateFilePath, $toString, $isString));
        extract($this->variables);
        ob_start();

        // parsing a string (moved from drawString method)
        if ($isString)
            require $this->checkString($templateFilePath);
        else // parsing a template file
            require $this->checkTemplate($templateFilePath);

        $html = ob_get_clean();

        if ($this->getConfigurationKey('raintpl3_plugins_compatibility'))
        {
            // Execute plugins, before_parse
            $context = $this->getPlugins()->createContext(array(
                'code' => $html,
                'conf' => $this->config,
            ));

            $this->getPlugins()->run('afterDraw', $context);
            $html = $context->code;
        }

        list($templateFilePath, $toString, $isString, $html) = $this->executeEvent('engine.draw.after', array($templateFilePath, $toString, $isString, $html));

        if ($toString)
            return $html;
        else
            echo $html;
    }

    /**
     * Evaluate a template string
     *
     * @param string $string Input template as string
     * @param bool $toString Return as string instead of printing?
     *
     * @return void|string Compiled string (if $toString is set to True)
     */
    public function drawString($string, $toString = false)
    {
        return $this->draw($string, $toString, True);
    }

    /**
     * Assign variable
     * eg.     $t->assign('name','mickey');
     *
     * @param mixed $variable Name of template variable or associative array name/value
     * @param mixed $value value assigned to this variable. Not set if variable_name is an associative array
     *
     * @return \Rain\Tpl $this
     */
    public function assign($variable, $value = null)
    {
        if (is_array($variable))
            $this->variables = $variable + $this->variables;
        else
            $this->variables[$variable] = $value;

        if ($this->getConfigurationKey('debug'))
        {
            $this->debugData['assigns'][] = array(
                'variable' => $variable,
                'value' => $value,
                'stacktrace' => debug_backtrace(),
            );
        }

        return $this;
    }

    /**
     * Clean the expired files from
     *
     * @param int $expireTime Expiration time
     *
     * @event engine.clean $files
     * @return string[] List of removed files
     */
    public function clean($expireTime = 2592000)
    {
        $files = glob($this->getConfigurationKey('cache_dir') . "*.rtpl.php");
        $time = time() - $expireTime;
        $removed = array();

        // plugins support
        $files = $this->executeEvent('engine.clean', $files);

        foreach ($files as $file)
        {
            if ($time > filemtime($file))
            {
                $removed[] = $file;
                unlink($file);
            }
        }

        return $removed;
    }

    /**
     * Allows the developer to register a regular expression parsed tag
     *
     * @param string $tag Tag name
     * @param string $parse Regular expression to parse the tag
     * @param callable $function Function to call to parse a tag
     *
     * @event engine.registerTag $tag
     * @return array
     */
    public function & registerRegexpTag($tag, $parse, $function)
    {
        $this->registeredTags[$tag] = array(
            'parse' => $parse,
            'function' => $function,
        );

        $this->executeEvent('engine.registerRegexpTag', $tag);
        return $this->registeredTags;
    }


    /**
     * Resolve template path
     *
     * @param string $template Template name, path, or absolute path
     * @param array $templateDirectories (Optional) List of included directories
     * @param string $parentTemplateFilePath (Optional) Path to template that included this template
     * @param string $defaultExtension (Optional) Default file extension to append when no extension specified
     *
     * @author Damian Kęska <damian.keska@fingo.pl>
     * @return string|null
     */
    public static function resolveTemplatePath($template, $templateDirectories = null, $parentTemplateFilePath = null, $defaultExtension = null)
    {
        // add default extension in case there is no any
        if (!pathinfo($template, PATHINFO_EXTENSION) && $defaultExtension)
        {
            $extension = "." . $defaultExtension;
            $template = $template . $extension;
        }

        $path = '';
        $tplDir = array();

        if (is_array($templateDirectories))
            $tplDir = $templateDirectories;

        elseif (!is_array($templateDirectories) || is_string($templateDirectories))
            $tplDir = array($templateDirectories);

        // include current directory
        if ($parentTemplateFilePath) $tplDir[] = dirname($parentTemplateFilePath);
        $tplDir[] = '';

        foreach ($tplDir as $dir)
        {
            if (is_file($dir . '/' . $template))
                $path = $dir . '/' . $template;
            elseif (is_file($dir . '/' . $template . '.tpl'))
                $path = $dir . '/' . $template . '.tpl';

            if ($path) break;
        }

        return $path;
    }

    /**
     * Check if the template exist and compile it if necessary
     *
     * @param string $template Name of the file of the template
     * @param string|null $parentTemplateFilePath (Optional) Parent template file path (that template which is including this one)
     * @param int|null|numeric $parentTemplateLine (Optional) Line from parent template that called this method
     * @param int|null|numeric $parentTemplateOffset (Optional) Offset of parent template where is this function called
     * @param bool $forceCompile (Optional) Force template compilation
     *
     * @throws NotFoundException
     * @throws Tpl\Exception
     * @throws Tpl_Exception
     * @throws string
     * @event engine.checkTemplate.path $path
     * @event engine.checkTemplate.compile $path, $parsedTemplateFilepath
     * @event engine.checkTemplate.parsedTemplateFilePath $parsedTemplateFilepath
     * @return string Compiled template absolute path
     */
    protected function checkTemplate($template, $parentTemplateFilePath = null, $parentTemplateLine = null, $parentTemplateOffset = null, $forceCompile = false)
    {
        $originalTemplate = $template;
        $extension = '';

        $path = self::resolveTemplatePath($template, $this->getConfigurationKey('tpl_dir'), $parentTemplateFilePath, $this->getConfigurationKey('tpl_ext'));

        // normalize path
        $path = str_replace(array('//', '//'), '/', $path);

        $parsedTemplateFilepath = $this->getConfigurationKey('cache_dir') . basename($originalTemplate) . "." . md5(dirname($path) . serialize($this->getConfigurationKey('checksum')) . $originalTemplate) . '.rtpl.php';

        // plugins
        if (!$this->executeEvent('engine.checkTemplate.parsedTemplateFilePath', $parsedTemplateFilepath))
        {
            $forceCompile = true;
        }

        $path = $this->executeEvent('engine.checkTemplate.path', $path);

        // if the template doesn't exsist throw an error
        if (!$path && $path !== false)
        {
            $traceString = '';

            if ($parentTemplateFilePath && $parentTemplateLine && $parentTemplateOffset)
                $traceString = ', included from "' .$parentTemplateFilePath. '" on line ' .$parentTemplateLine. ', offset ' .$parentTemplateOffset;

            $e = new Tpl\NotFoundException('Template ' . $originalTemplate . ' not found' .$traceString);
            throw $e->templateFile($originalTemplate);
        }

        /**
         * Check if there is an already compiled version
         *
         * @config bool allow_compile
         */
        if (!$this->getConfigurationKey('allow_compile') && !$forceCompile)
        {
            // check if there is a compiled version
            if (!is_file($parsedTemplateFilepath))
            {
                // allow first compilation of file
                if (!$this->getConfigurationKey('allow_compile_once'))
                    throw new NotFoundException('Template cache file "' .$parsedTemplateFilepath. '" is missing and "allow_compile", "allow_compile_once" are disabled in configuration');

            } else {

                // allow template re-compiling ondemand for plugins
                if (!$forceCompile)
                {
                    return $parsedTemplateFilepath;
                }
            }
        }

        /**
         * Run the parser if file was not updated since last compilation time
         */
        if ($this->getConfigurationKey('debug') or !is_file($parsedTemplateFilepath) or ( filemtime($parsedTemplateFilepath) < filemtime($path)) or $forceCompile)
        {
            list($path, $parsedTemplateFilepath) = $this->executeEvent('engine.checkTemplate.compile', array($path, $parsedTemplateFilepath));
            $parser = new Tpl\Parser($this);
            $parser->compileFile($path, $parsedTemplateFilepath);
        }

        return $parsedTemplateFilepath;
    }

    /**
     * Compile a string if necessary
     *
     * @param string $string RainTpl template string to compile
     *
     * @event engine.checkString $string
     * @return string full filepath that php must use to include
     */
    protected function checkString($string)
    {
        $string = $this->executeEvent('engine.checkString', $string);

        // set filename
        $templateName = md5($string . implode($this->getConfigurationKey('checksum')));
        $parsedTemplateFilepath = $this->getConfigurationKey('cache_dir') . $templateName . '.s.rtpl.php';

        // Compile the template if the original has been updated
        if ($this->getConfigurationKey('debug') || !file_exists($parsedTemplateFilepath))
        {
            $parser = new Tpl\Parser($this);
            $parser->compileString($templateName, $parsedTemplateFilepath, $string);
        }

        return $parsedTemplateFilepath;
    }
}

class RestrictedException extends Tpl\Exception {};
class InvalidConfiguration extends Tpl\Exception {};