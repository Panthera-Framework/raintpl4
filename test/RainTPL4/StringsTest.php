<?php
/**
 * Test {"string"|modifier} syntax
 *
 * @package RainTPL4\syntax\strings\tests
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

    /**
     * Parser::getQuotesPositions() - detects substrings (strings in quotes) in a string
     *
     * <code>"This is a test string", "This is a test string with \"escaped\" quotes inside", 'Test', 'Another test\'s'</code>
     * <data-string0>"This is a test string"</data-string0>
     * <data-string1>"This is a test string with \"escaped\" quotes inside"</data-string1>
     * <data-string2>'Test'</data-string2>
     * <data-string3>'Another test\'s'</data-string3>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testgetQuotesPositionsInternalFunction()
    {
        $code = $this->getTestCodeFromPHPDoc();
        $quotes = \Rain\Tpl\Parser::getQuotesPositions($code);

        // 4 pairs of quotes
        $this->assertEquals(4, count($quotes));

        // verify strings inside
        foreach ($quotes as $key => $string)
        {
            $stringContent = substr($code, $string[1], (($string[2] - $string[1]) + 1));
            $this->assertEquals($this->getExampleDataFromPHPDoc('string' .$key), $stringContent);
        }
    }
}