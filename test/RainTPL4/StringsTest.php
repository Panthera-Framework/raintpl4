<?php
/**
 * Test {"string"|modifier} syntax
 *
 * @author Damian Kęska <damian.keska@fingo.pl>
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class StringsTest extends RainTPLTestCase
{
    public function testSingleFunctionStrippingWhitespaces()
    {
        $this->setupRainTPL4();
        $this->assertEquals('test', $this->engine->drawString("{' test '|trim}", true));
    }

    public function testSingleFunctionStrippingWhitespacesDoubleQuotes()
    {
        $this->setupRainTPL4();
        $this->assertEquals('test', $this->engine->drawString('{" test "|trim}', true));
    }

    public function singleArgumentsInModifiers()
    {
        $this->setupRainTPL4();
        $this->assertEquals('te', $this->engine->drawString('{"test"|substr:2}', true));
    }

    public function multipleArgumentsInModifiers()
    {
        $this->setupRainTPL4();
        $this->assertEquals('te', $this->engine->drawString("{'test'|substr:0,2}", true));
    }

    public function modifierMultipleArgumentsPlusSecondModifier()
    {
        $this->setupRainTPL4();
        $this->assertEquals('te', $this->engine->drawString('{" test "|substr:0,2|trim}', true));
    }

    public function simpleFunctionTestInPHPWay()
    {
        $this->setupRainTPL4();
        $this->assertEquals('te', $this->engine->drawString('{trim(substr(" test ", 0, 2))}', true));
    }

    /**
     * Escaping quotes with "\" test
     *
     * <code>{if "key1\""|in:$arrayVariable}OK{/if}</code>
     * <expects>OK</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */

    public function testEscapedQuotes()
    {
        $this->setupRainTPL4();
        $this->engine->assign('arrayVariable', array('key1"' => 'value1'));

        $this->autoAssertEquals();
    }
}