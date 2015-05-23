<?php
namespace Rain;

/**
 * RainTPL3 compatibility layer on RainTPL4
 *
 * @package Rain\Tpl
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class Tpl extends RainTPL4
{
    public static $staticConfiguration = array();
    public static $__instances = array();
    public static $deprecatedRegisteredTags = array();
    public static $deprecatedPlugins = array();

    /**
     * Merge static configuration with object configuration
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = array_merge(static::$staticConfiguration, $this->config);
        static::$__instances[] = &$this;
    }

    /**
     * Configure RainTPL globally
     *
     * @deprecated
     *
     * @param string|array $setting
     * @param mixed|null $value
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool|null
     */
    public static function configure($setting, $value = null, $object = null)
    {
        if ($setting == "tpl_dir" || $setting == "cache_dir")
        {
            $value = self::addTrailingSlash($value);
        }

        if (is_array($setting))
        {
            foreach ($setting as $key => $value)
            {
                self::configure($key, $value, $object);
            }

            return true;
        }

        if ($object instanceOf Tpl)
        {
            $object->config[$key] = $value;
            return true;
        }

        self::$staticConfiguration[$setting] = $value;

        /**
         * Update all instances
         */
        if (self::$__instances)
        {
            foreach (self::$__instances as $instance)
            {
                if ($instance instanceOf Tpl)
                {
                    $instance->setConfigurationKey($setting, $value);
                }
            }
        }

        return true;
    }

    /**
     * Configure a RainTPL instance
     *
     * @deprecated
     * @param string|array $setting
     * @param mixed $value
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool|null
     */
    public function objectConfigure($setting, $value)
    {
        return static::configure($setting, $value, $object);
    }


    /**
     * Add a trailing slash
     *
     * @deprecated
     * @param $folder
     * @return string
     */
    protected static function addTrailingSlash($folder)
    {
        if (is_array($folder))
        {
            foreach($folder as &$f)
            {
                $f = self::addTrailingSlash($f);
            }

        } elseif ( strlen($folder) > 0 && $folder[0] != '/' ) {
            $folder = $folder . "/";
        }

        return $folder;
    }

    /**
     * Allows the developer to register a tag.
     *
     * @param string $tag nombre del tag
     * @param regexp $parse regular expression to parse the tag
     * @param anonymous function $function: action to do when the tag is parsed
     *
     * @return array|void
     */
    public static function registerTag($tag, $parse, $function)
    {
        static::$deprecatedRegisteredTags[$tag] = array(
            "parse" => $parse,
            "function" => $function,
        );
    }

    /**
     * Registers a plugin globally.
     *
     * @param \Rain\Tpl\IPlugin $plugin
     * @param string $name name can be used to distinguish plugins of same class.
     */
    public static function registerPlugin(Tpl\IPlugin $plugin, $name = '') {
        $name = (string)$name ?: \get_class($plugin);
        static::getPlugins()->addPlugin($name, $plugin);
    }
    /**
     * Removes registered plugin from stack.
     *
     * @param string $name
     */
    public static function removePlugin($name) {
        static::getPlugins()->removePlugin($name);
    }
    /**
     * Returns plugin container.
     *
     * @return \Rain\Tpl\PluginContainer
     */
    protected static function getPlugins() {
        return static::$deprecatedPlugins
            ?: static::$deprecatedPlugins = new Tpl\PluginContainer();
    }
}