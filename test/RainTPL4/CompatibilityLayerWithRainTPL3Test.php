<?php
/**
 * RainTPL3 compatibility layer test
 *
 * @package RainTPL4\backwardsCompatibility
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CompatibilityLayerWithRainTPL3Test extends RainTPLTestCase
{
    /**
     * Set a STATIC (shared between instances) configuration key after instance was initialized
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testStaticConfigurationBeforeInstance()
    {
        $this->setup();
        Rain\Tpl::configure('my-config-key', 'this is a test value');
        $this->assertEquals('this is a test value', $this->engine->getConfigurationKey('my-config-key'));
    }

    /**
     * Set a STATIC (shared between instances) configuration key BEFORE creating of instance
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testStaticConfigurationAfterInstance()
    {
        Rain\Tpl::configure('my-config-key', 'this is a test value');
        $this->setup();
        $this->assertEquals('this is a test value', $this->engine->getConfigurationKey('my-config-key'));
    }
}