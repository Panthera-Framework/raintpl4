<?php
namespace Rain\Tpl;

/**
 * RainTPL4 configuration functions, shared by engine and the parser
 *
 * @package Rain\Modules
 * @author Damian Kęska <damian@pantheraframework.org>
 */
trait RainTPLConfiguration
{
    /*private $config = array(

    );*/

    /**
     * Get configuration value by key name
     *
     * @param string $key Configuration key name
     * @param mixed $defaults (Optional) Default value
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return mixed|null
     */
    public function getConfigurationKey($key, $defaults = null)
    {
        if (!isset($this->config[$key]))
        {
            $this->config[$key] = $defaults;
        }

        return $this->config[$key];
    }

    /**
     * Set a configuration and return reference to it's value
     *
     * @param string $key Key name
     * @param mixed $value Value
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return mixed
     */
    public function & setConfigurationKey($key, $value)
    {
        $this->config[$key] = $value;

        return $this->config[$key];
    }

    /**
     * Get configuration array
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Import configuration keys
     *
     * @param array $config Input array
     * @param bool $merge (Optional) Merge configuration keys?
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return void|bool
     */
    public function setConfiguration(array $config, $merge = true)
    {
        if ($merge)
        {
            $this->config = $config + (array)$this -> config;
            return true;
        }

        $this->config = $config;
    }
}