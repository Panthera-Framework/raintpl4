<?php
/**
 * {capture}, {print}, {autoescape} tags test
 *
 * @package RainTPL4\syntax\capture\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CaptureTest extends RainTPLTestCase
{
    /**
     * Base test
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBaseCaptureBlock()
    {
        $this->setupRainTPL4();
        $this->assertEquals('This is a capture tag test', $this->engine->drawString("{capture name=\"test\"}This is a capture tag test{/capture}{\$test}", true));
    }

    /**
     * Include a print="true" argument
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBaseCaptureBlockWithPrint()
    {
        $this->setupRainTPL4();
        $this->assertEquals('This is a capture tag test', $this->engine->drawString("{capture name=\"test\" print=\"true\"}This is a capture tag test{/capture}", true));
    }

    /**
     * Use filter="trim" (to filter output using trim() function)
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testCaptureBlockWithTrimFilter()
    {
        $this->setupRainTPL4();
        $this->assertEquals('This is a capture tag test', $this->engine->drawString("{capture name=\"test\" print=\"true\" filter=\"trim\"} This is a capture tag test {/capture}", true));
    }

    /**
     * Use filter="substr", filterArg="0" and filterArg2="8" (to cut off output) which should compile to substr($output, 0, 8)
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testCaptureBlockWithSubstrFilterAndArguments()
    {
        $this->setupRainTPL4();
        $this->assertEquals(' This is', $this->engine->drawString("{capture name=\"test\" print=\"true\" filter=\"substr\" filterArg1=\"0\" filterArg2=\"8\"} This is a capture tag test {/capture}", true));
    }
}