<?php
namespace Rain\Tpl;
use Rain\RainTPL4;

/**
 * Abstract interface class for RainTPL4 plugins
 *
 * @package Rain\Tpl
 * @author Damian Kęska <damian@pantheraframework.org>
 */
abstract class RainTPL4Plugin
{
    /**
     * @var RainTPL4 $engine
     */
    public $engine;

    /**
     * @var string[]
     */
    protected $defaultConfig = array();

    /**
     * Apply default configuration, run $this->init(), save RainTPL4 instance
     *
     * @param Rain\\RainTPL4 $rainTPLInstance
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function __construct($rainTPLInstance)
    {
        $this->engine = $rainTPLInstance;

        /**
         * Apply plugin's default configuration to the RainTPL4 instance
         */
        if (isset($this->defaultConfig) && $this->defaultConfig)
        {
            foreach ($this->defaultConfig as $key => $value)
            {
                // this code couldn't be done using RainTPLConfiguration, also it has better performance
                if (!isset($rainTPLInstance->config[$key]))
                {
                    $rainTPLInstance->config[$key] = $value;
                }
            }
        }
    }
}