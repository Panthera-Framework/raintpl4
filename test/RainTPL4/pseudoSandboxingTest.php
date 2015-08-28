<?php
/**
 * pseudoSandboxing RainTPL4 plugin tests
 *
 * @package RainTPL4\plugins\pseudoSandboxing\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class pseudoSandboxingTest extends RainTPLTestCase
{
    /**
     * Base test
     *
     * @expectedException Rain\RestrictedException
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testWhitelist()
    {
        $this->setupRainTPL4();
        $this->engine->setConfigurationKey('sandboxMode', 'whitelist');
        $this->engine->setConfigurationKey('pluginsEnabled', array(
            'pseudoSandboxing',
        ));

        $this->engine->drawString('{shell_exec("ls -la")}', true);
    }

    /**
     * Blacklist test
     *
     * @expectedException Rain\RestrictedException
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBlacklist()
    {
        $this->setupRainTPL4();

        // add our testing method to whitelist to make sure the whitelist will not be used instead of blacklist
        $whitelist = $this->engine->getConfigurationKey('sandboxWhitelist');
        $whitelist[] = 'shell_exec';

        // configure blacklist
        $blacklist = $this->engine->getConfigurationKey('sandboxBlacklist');
        $blacklist[] = 'shell_exec';

        $this->engine->setConfigurationKey('sandboxWhitelist', $whitelist);
        $this->engine->setConfigurationKey('sandboxBlacklist', $blacklist);
        $this->engine->setConfigurationKey('sandboxMode', 'blacklist');
        $this->engine->setConfigurationKey('pluginsEnabled', array(
            'pseudoSandboxing',
        ));

        $this->engine->drawString('{shell_exec("ls -la")}', true);
    }
}