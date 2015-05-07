<?php
/**
 * Test builtin modifier functions
 *
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class BuiltinModifiersTest extends RainTPLTestCase
{
    /**
     * Test built-in function modificators, on "replace" example
     * {...|replace:"from":"to"}
     *
     * <code>{"This is not working correctly."|replace:"is not working correctly":"is working perfectly"}</code>
     * <expects>This is working perfectly.</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBuiltinReplaceModifierOnString()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }

    /**
     * Test built-in function modificators, on "replace" example
     * {$testString|replace:"from":"to"}
     *
     * <code>{$testString|replace:"is not working correctly":"is working perfectly"}</code>
     * <expects>This is working perfectly.</expects>
     * <data-testString>This is not working correctly.</data-testString>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBuiltinReplaceModifierOnVariable()
    {
        $this->setupRainTPL4();
        $this->engine->assign('testString', $this->getExampleDataFromPHPDoc('testString'));
        $this->autoAssertEquals();
    }

    /**
     * cut modifier test
     *
     * <code>{"This is a very long string"|cut:12}</code>
     * <expects>This is a...</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBuiltinModifierCut()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }
}