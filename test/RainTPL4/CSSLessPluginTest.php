<?php
/**
 * Testcases for integration with CSS syntax compilers LESS and SASS
 *
 * @package RainTPL4\syntax\inlineParsers\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CSSLessPluginTest extends RainTPLTestCase
{
    /**
     * Base test
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBaseSASS()
    {
        $this->setupRainTPL4();
        $this->engine->setConfigurationKey('pluginsEnabled', array(
            'CSSLess',
        ));

        $compiled = $this->engine->drawString('This is a test
            <style type="text/sass">.test { font-color: white; }</style>

            <style type="text/sass">$font-stack:    Helvetica, sans-serif;
$primary-color: #333;

body {
  font: 100% $font-stack;
  color: $primary-color;
}</style>', true);

        $this->assertEquals('This is a test
            <style type="text/css">.test {
  font-color: white;
}
</style>

            <style type="text/css">body {
  font: 100% Helvetica, sans-serif;
  color: #333;
}
</style>', $compiled);
    }
}