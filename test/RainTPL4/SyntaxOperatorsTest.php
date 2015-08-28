<?php
/**
 * Syntax operators test eg. "in" / string[0..1] / array[0:5]
 *
 * @package RainTPL4\syntax\in\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class SyntaxOperatorsTest extends RainTPLTestCase
{
    /**
     * "in" comparison operator test
     *
     * <code>{if "test" in $array}Contains{else}Not containing :({/if}</code>
     * <expects>Contains</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testSimpleInOperator()
    {
        $this->setupRainTPL4();
        $this->engine->assign('array', array(
            'test',
        ));
        $this->autoAssertEquals();
    }

    /**
     * "in" comparison operator test
     *
     * <code>{if "test" in $array and 2 > 1}Contains{else}Not containing :({/if}</code>
     * <expects>Contains</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testSimpleInOperatorWithAnd()
    {
        $this->setupRainTPL4();
        $this->engine->assign('array', array(
            'test',
        ));
        $this->autoAssertEquals();
    }

    /**
     * "in" comparison operator test with quotes, function, logic "AND" operator
     *
     * <code>{if "test" in $array and strtolower("this is a test")}Contains{else}Not containing :({/if}</code>
     * <expects>Contains</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testSimpleInOperatorWithAndString()
    {
        $this->setupRainTPL4();
        $this->engine->assign('array', array(
            'test',
        ));
        $this->autoAssertEquals();
    }

    /**
     * "in" comparison operator test with modifier
     *
     * <code>{if "test" in $array and "THIS IS A TEST"|strtolower}Contains{else}Not containing :({/if}</code>
     * <expects>Contains</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testSimpleInOperatorWithAndModifier()
    {
        $this->setupRainTPL4();
        $this->engine->assign('array', array(
            'test',
        ));
        $this->autoAssertEquals();
    }

    /**
     * "in" comparison operator in functionBlockParser
     *
     * <code>{"test" in $array}</code>
     * <expects>1</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testInArrayInFunctionBlocks()
    {
        $this->setupRainTPL4();
        $this->engine->assign('array', array(
            'test',
        ));
        $this->autoAssertEquals();
    }

    /**
     * "in" comparison operator for strings only
     *
     * <code>{"test" in "dddddddddddd"}, {"b" in "abc"}</code>
     * <expects>, 1</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testStringInString()
    {
        $this->setupRainTPL4();
        //$this->engine->setConfigurationKey('print_parsed_code', true);
        $this->autoAssertEquals();
    }
}