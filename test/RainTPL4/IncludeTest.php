<?php
/**
 * {include} tag testcase
 *
 * @package RainTPL4\syntax\include\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class IncludeTest extends RainTPLTestCase
{
    /**
     * Test relative paths
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testRelativePathInclude()
    {
        $this->setupRainTPL4();
        //$this->engine->setConfigurationKey('print_parsed_code', true);
        $this->assertEquals('Header loaded successfully!', $this->engine->drawString('{include file="includes/header.html"}', true));
    }

    public function testAbsolutePathInclude()
    {
        $this->setupRainTPL4();
        $path = realpath(__DIR__. '/../../templates/');
        //$this->engine->setConfigurationKey('print_parsed_code', true);
        $this->assertEquals('Header loaded successfully!', $this->engine->drawString('{include file="' .$path. '/includes/header.html"}', true));
    }

    /**
     * {include path/to/test.html} - without quotes
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testMoreNaturalSyntax()
    {
        $this->setupRainTPL4();
        $path = realpath(__DIR__. '/../../templates/');
        //$this->engine->setConfigurationKey('print_parsed_code', true);
        $this->assertEquals('Header loaded successfully!', $this->engine->drawString('{include ' .$path. '/includes/header.html}', true));
    }

    /**
     * {include "path/to/test.html"} - with quotes
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testMoreNaturalSyntaxWithQuotes()
    {
        $this->setupRainTPL4();
        $path = realpath(__DIR__. '/../../templates/');
        //$this->engine->setConfigurationKey('print_parsed_code', true);
        $this->assertEquals('Header loaded successfully!', $this->engine->drawString('{include "' .$path. '/includes/header.html"}', true));
    }
}