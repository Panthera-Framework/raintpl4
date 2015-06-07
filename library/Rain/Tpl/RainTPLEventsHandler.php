<?php
namespace Rain\Tpl;

/**
 * RainTPL4 Events Handler
 *
 * @package Rain\Modules
 * @author Damian Kęska <damian@pantheraframework.org>
 */
trait RainTPLEventsHandler
{
    /**
     * List of eventNames and it's callbacks
     *
     * @var array[]
     */
    public $events = array(

    );

    /**
     * @var object[]
     */
    public $__eventHandlers = array(

    );

    /**
     * Include paths
     *
     * @var string[]
     */
    public $__eventHandlersIncludePath = array(

    );

    /**
     * List of plugin names that will be loaded automaticaly
     *
     * @var string[]
     */
    public $__eventHandlersEnabledByDefault = array(

    );

    protected $__eventsSortingCache = array();

    /**
     * Connect a function callback
     *
     * @param string $eventName Event to connect to
     * @param callable $callable Callable function
     * @param null $priority (Optional) Priority
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    public function connectEvent($eventName, $callable, $priority = null)
    {
        if (!isset($this->events[$eventName]))
            $this->events[$eventName] = array();

        if (!is_callable($callable))
            return false;

        if ($priority && is_int($priority) && !isset($events[$eventName][$priority]))
            $this->events[$eventName][$priority] = $callable;
        else
            $this->events[$eventName][] = $callable;

        if (isset($this->__eventsSortingCache[$eventName]))
            unset($this->__eventsSortingCache[$eventName]);

        return true;
    }

    /**
     * Execute all listeners on selected event
     *
     * @param string $eventName Event name to execute actions for
     * @param mixed $data (Optional) Input data
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return mixed
     */
    public function executeEvent($eventName, $data = null)
    {
        if (!isset($this->events[$eventName]) || !count($this->events[$eventName], COUNT_RECURSIVE))
        {
            return $data;
        }

        // sort descending
        if (!isset($this->__eventsSortingCache[$eventName]))
        {
            ksort($this->events[$eventName]);
            $this->events[$eventName] = array_reverse($this->events[$eventName]);
            $this->__eventsSortingCache[$eventName] = true;
        }

        foreach ($this->events[$eventName] as $priority => $eventHandlerCallableFunction)
        {
            $callbackData = $eventHandlerCallableFunction($data);

            if (!is_null($callbackData))
                $data = $callbackData;
        }

        return $data;
    }

    /**
     * Load plugins from it's directories
     *
     * @listens engine.draw.before
     */
    public function loadEventHandlers()
    {
        if (!$this->__eventHandlersIncludePath && $this->getConfigurationKey('pluginsIncludePath'))
            $this->__eventHandlersIncludePath += $this->getConfigurationKey('pluginsIncludePath');

        if ($this->getConfigurationKey('pluginsEnabled') && !$this->__eventHandlersEnabledByDefault)
            $this->__eventHandlersEnabledByDefault = array_merge($this->__eventHandlersEnabledByDefault, $this->getConfigurationKey('pluginsEnabled'));

        if (!$this->__eventHandlersIncludePath || !$this->__eventHandlersEnabledByDefault)
            return false;

        foreach ($this->__eventHandlersIncludePath as $path)
        {
            if (is_dir($path))
            {
                foreach ($this->__eventHandlersEnabledByDefault as $pluginName)
                {
                    if (is_file($path . '/' .$pluginName. '/' .$pluginName. '.RainTPLPlugin.php') && !isset($this->__eventHandlers[$pluginName]))
                    {
                        $this->loadEventsHandler($pluginName, $path . '/' .$pluginName. '/' .$pluginName. '.RainTPLPlugin.php');
                    }
                }
            }
        }
    }

    /**
     * Load events handler (plugin)
     *
     * @param string $name Events handler name
     * @param string $path Path to include
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool|object
     */
    public function loadEventsHandler($name, $path = '')
    {
        if ($path && is_file($path))
            include_once $path;

        if (!class_exists($name))
            return false;

        $this->__eventHandlers[$name] = new $name($this);

        if (method_exists($this->__eventHandlers[$name], 'init'))
        {
            $this->__eventHandlers[$name]->init();
        }

        return $this->__eventHandlers[$name];
    }
}