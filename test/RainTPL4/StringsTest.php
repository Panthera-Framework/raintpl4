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

    /**
     * Test quotes escaping of internal function Parser::strposa()
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testStrposaInternalFunction()
    {
        $pos = -1;
        for ($i = 0; $i < 3; $i++)
        {
            $pos = \Rain\Tpl\Parser::strposa('"key1\""|in:$arrayVariable', array('"', "'"), $pos + 1);

            $positions[] = $pos;
        }

        $this->assertEquals(array(0, 7, false), $positions);
    }
}