<?php
/**
 * RainTPL4 Events Handler testcase
 *
 * @package RainTPL4\plugins\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class PluginsTest extends RainTPLTestCase
{
    /**
     * Simply modify output after compiling a template
     *
     * @param array $input array($templateFilePath, $toString, $isString, $html)
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array $input Modified input
     */
    public function filterDrawFunction($input)
    {
        $input[3] = str_replace('failed', 'passed', $input[3]);
        return $input;
    }

    /**
     * Simply modify output after compiling a template
     *
     * @param array $input array($templateFilePath, $toString, $isString, $html)
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return array $input Modified input
     */
    public function filterDrawFunctionSecond($input)
    {
        $input[3] .= ', great!';
        $input[3] = strtoupper($input[3]);
        return $input;
    }

    /**
     * Base test
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testSimpleFilteringOutputEvent()
    {
        $this->setupRainTPL4();
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunction'));
        $this->assertEquals('This test was passed', $this->engine->drawString('This test was failed', true));
    }

    /**
     * Test priority feature
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testEventsPriority()
    {
        $this->setupRainTPL4();
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunction'), 200);
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunctionSecond'), 100);
        $this->assertEquals('#1 THIS TEST WAS PASSED, GREAT!', $this->engine->drawString('#1 This test was failed', true));
    }

    /**
     * Test priority feature #1
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testEventsPrioritySecond()
    {
        $this->setupRainTPL4();
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunctionSecond'), 100);
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunction'), 200);
        $this->assertEquals('#2 THIS TEST WAS PASSED, GREAT!', $this->engine->drawString('#2 This test was failed', true));
    }

    /**
     * Test priority feature #1
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testEventsPriorityThird()
    {
        $this->setupRainTPL4();
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunctionSecond'));
        $this->engine->connectEvent('engine.draw.after', array($this, 'filterDrawFunction'));
        $this->assertEquals('#3 THIS TEST WAS PASSED, GREAT!', $this->engine->drawString('#3 This test was failed', true));
    }
}

